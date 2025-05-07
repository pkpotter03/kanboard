<?php

namespace Kanboard\Controller;

use Kanboard\Core\Controller\AccessForbiddenException;
use Kanboard\Model\UserModel;

/**
 * Voice Command Controller
 *
 * @package Kanboard\Controller
 */
class VoiceCommandController extends BaseController
{
    /**
     * Process voice commands
     *
     * @access public
     */
    public function process()
    {
        // Only check CSRF if enabled in config
        if (defined('CSRF_ENABLED') && CSRF_ENABLED) {
            $this->checkReusableCSRFParam();
        }
        
        if (!$this->request->isAjax()) {
            throw new AccessForbiddenException();
        }

        // Get JSON data from request body
        $data = $this->request->getJson();
        
        // Debug: Log received data
        error_log("Received JSON data: " . json_encode($data));
        
        $command = isset($data['command']) ? $data['command'] : '';
        $project_id = isset($data['project_id']) ? (int)$data['project_id'] : 0;
        $task_id = isset($data['task_id']) ? (int)$data['task_id'] : 0;
        
        // Debug: Log extracted values
        error_log("Extracted command: " . $command);
        error_log("Extracted project_id: " . $project_id);
        error_log("Extracted task_id: " . $task_id);
        
        $response = array(
            'status' => 'success',
            'message' => '',
            'action' => '',
            'data' => null
        );

        // Normalize and log the command
        $normalizedCommand = strtolower(trim($command));
        error_log("Normalized command: " . $normalizedCommand);

        // First, define the number map at the start of the process function
        $numberMap = [
            'first' => 1, 'second' => 2, 'third' => 3, 'fourth' => 4, 'fifth' => 5,
            'sixth' => 6, 'seventh' => 7, 'eighth' => 8, 'ninth' => 9, 'tenth' => 10,
            'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5,
            'six' => 6, 'seven' => 7, 'eight' => 8, 'nine' => 9, 'ten' => 10
        ];

        // 1. Assign task command
        if (preg_match('/^(assign|sin|assigned|design)\s+(first|second|third|fourth|fifth|sixth|seventh|eighth|ninth|tenth|\d+(?:st|nd|rd|th)?)\s+task\s+(?:to|2|two|too)\s+(.+)$/i', $command, $matches)) {
            $taskPosition = isset($numberMap[strtolower($matches[2])]) 
                ? $numberMap[strtolower($matches[2])] 
                : (int)preg_replace('/(?:st|nd|rd|th)$/', '', $matches[2]);
            
            $assigneeName = $matches[3];
            
            // Try to find the user by username first
            $user = $this->userModel->getByUsername($assigneeName);
            
            // If not found by username, try to find by searching users
            if (!$user) {
                $users = $this->db
                    ->table(UserModel::TABLE)
                    ->beginOr()
                    ->ilike('username', $assigneeName)
                    ->ilike('name', $assigneeName)
                    ->closeOr()
                    ->eq('is_active', 1)
                    ->findAll();
                
                if (count($users) === 1) {
                    $user = $users[0];
                } elseif (count($users) > 1) {
                    $response['status'] = 'error';
                    $response['message'] = t('Multiple users found with name: ') . $assigneeName;
                    $response['action'] = 'alert';
                    $this->response->json($response);
                    return;
                }
            }
            
            if (!$user) {
                $response['status'] = 'error';
                $response['message'] = t('User not found: ') . $assigneeName;
                $response['action'] = 'alert';
            } else {
                // If no project_id is provided, try to get the current project
                if (!$project_id) {
                    $project_id = $this->request->getIntegerParam('project_id');
                }
                
                if (!$project_id) {
                    $response['status'] = 'error';
                    $response['message'] = t('Please select a project first');
                    $response['action'] = 'alert';
                } else {
                    // Get tasks in the current project ordered by position
                    $tasks = $this->taskFinderModel->getAll($project_id);
                    
                    // Check if the position is valid
                    if ($taskPosition < 1 || $taskPosition > count($tasks)) {
                        $response['status'] = 'error';
                        $response['message'] = t('Invalid task position. Available tasks: ') . count($tasks);
                        $response['action'] = 'alert';
                    } else {
                        // Get the task at the specified position (1-based index)
                        $task = $tasks[$taskPosition - 1];
                        
                        // Update task owner
                        $result = $this->taskModificationModel->update([
                            'id' => $task['id'],
                            'owner_id' => $user['id']
                        ]);
                        
                        if ($result) {
                            $response['status'] = 'success';
                            $response['message'] = t('Task assigned to ') . ($user['name'] ?: $user['username']);
                            $response['action'] = 'reload';
                            $response['data'] = array(
                                'task_id' => $task['id'],
                                'project_id' => $project_id
                            );
                        } else {
                            $response['status'] = 'error';
                            $response['message'] = t('Unable to assign task');
                            $response['action'] = 'alert';
                        }
                    }
                }
            }
        }
        // 2. Move task command
        else if (preg_match('/^(?:move|put|set)\s+(first|second|third|fourth|fifth|sixth|seventh|eighth|ninth|tenth|\d+(?:st|nd|rd|th)?)\s+task\s+(?:to|in|into|in to)\s+(backlog|ready|work in progress|done)$/i', $command, $matches)) {
            $taskPosition = isset($numberMap[strtolower($matches[1])]) 
                ? $numberMap[strtolower($matches[1])] 
                : (int)preg_replace('/(?:st|nd|rd|th)$/', '', $matches[1]);
            $targetColumn = strtolower($matches[2]);
            
            // If no project_id is provided, try to get the current project
            if (!$project_id) {
                $project_id = $this->request->getIntegerParam('project_id');
            }
            
            if (!$project_id) {
                $response['status'] = 'error';
                $response['message'] = t('Please select a project first');
                $response['action'] = 'alert';
            } else {
                // Get tasks in the current project ordered by position
                $tasks = $this->taskFinderModel->getAll($project_id);
                
                // Check if the position is valid
                if ($taskPosition < 1 || $taskPosition > count($tasks)) {
                    $response['status'] = 'error';
                    $response['message'] = t('Invalid task position. Available tasks: ') . count($tasks);
                    $response['action'] = 'alert';
                } else {
                    // Get the task at the specified position (1-based index)
                    $task = $tasks[$taskPosition - 1];
                    
                    // Get all columns for the project
                    $columns = $this->columnModel->getList($project_id);
                    
                    // Find the target column ID
                    $targetColumnId = null;
                    foreach ($columns as $columnId => $columnName) {
                        if (strtolower($columnName) === $targetColumn) {
                            $targetColumnId = $columnId;
                            break;
                        }
                    }
                    
                    if ($targetColumnId) {
                        $this->taskPositionModel->movePosition(
                            $project_id,
                            $task['id'],
                            $targetColumnId,
                            1,
                            $task['swimlane_id']
                        );
                        $response['message'] = t('Task moved to ') . ucfirst($targetColumn);
                        $response['action'] = 'reload';
                    } else {
                        $response['status'] = 'error';
                        $response['message'] = t('Column not found: ') . ucfirst($targetColumn);
                        $response['action'] = 'alert';
                    }
                }
            }
        }
        // 3. Create task command
        else if (preg_match('/^(?:create|make|add)\s+(?:a\s+)?task\s+(.+)$/i', $command, $matches)) {
            $taskTitle = $matches[1];
            
            // Debug log
            error_log("Creating task with title: " . $taskTitle);
            
            if (!$project_id) {
                $project_id = $this->request->getIntegerParam('project_id');
            }
            
            if (!$project_id) {
                $response['status'] = 'error';
                $response['message'] = t('Please select a project first');
                $response['action'] = 'alert';
            } else {
                $taskData = array(
                    'title' => $taskTitle,
                    'project_id' => $project_id,
                    'creator_id' => $this->userSession->getId(),
                    'date_creation' => time(),
                    'column_id' => $this->columnModel->getFirstColumnId($project_id)
                );
                
                $task_id = $this->taskCreationModel->create($taskData);
                
                if ($task_id) {
                    $response['message'] = t('Task created successfully: ') . $taskTitle;
                    $response['action'] = 'reload';
                    $response['data'] = array(
                        'task_id' => $task_id,
                        'project_id' => $project_id
                    );
                } else {
                    $response['status'] = 'error';
                    $response['message'] = t('Unable to create task');
                }
            }
        }
        // 4. Create and assign task command
        else if (preg_match('/^(?:create|make|add)\s+(?:a\s+)?task\s+(.+?)(?:\s+and\s+assign(?:ed)?\s+(?:to|2|two|too)\s+)(.+)$/i', $command, $matches)) {
            $taskTitle = trim($matches[1]);  // Trim any extra spaces
            $assigneeName = trim($matches[2]);
            
            // Debug log
            error_log("Creating task with title: " . $taskTitle);
            error_log("Assigning to user: " . $assigneeName);
            
            // Try to find the user by username first
            $user = $this->userModel->getByUsername($assigneeName);
            
            // If not found by username, try to find by searching users
            if (!$user) {
                $users = $this->db
                    ->table(UserModel::TABLE)
                    ->beginOr()
                    ->ilike('username', $assigneeName)
                    ->ilike('name', $assigneeName)
                    ->closeOr()
                    ->eq('is_active', 1)
                    ->findAll();
                
                if (count($users) === 1) {
                    $user = $users[0];
                } elseif (count($users) > 1) {
                    $response['status'] = 'error';
                    $response['message'] = t('Multiple users found with name: ') . $assigneeName;
                    $response['action'] = 'alert';
                    $this->response->json($response);
                    return;
                }
            }
            
            if (!$user) {
                $response['status'] = 'error';
                $response['message'] = t('User not found: ') . $assigneeName;
            } else {
                // If no project_id is provided, try to get the current project
                if (!$project_id) {
                    $project_id = $this->request->getIntegerParam('project_id');
                }
                
                if (!$project_id) {
                    $response['status'] = 'error';
                    $response['message'] = t('Please select a project first');
                    $response['action'] = 'alert';
                } else {
                    // Create the task
                    $taskData = array(
                        'title' => $taskTitle,
                        'project_id' => $project_id,
                        'creator_id' => $this->userSession->getId(),
                        'date_creation' => time(),
                        'column_id' => $this->columnModel->getFirstColumnId($project_id),
                        'owner_id' => $user['id']  // Add owner_id to assign the task
                    );
                    
                    $task_id = $this->taskCreationModel->create($taskData);
                    
                    if ($task_id) {
                        $response['message'] = t('Task created and assigned to ') . ($user['name'] ?: $user['username']);
                        $response['action'] = 'reload';
                        $response['data'] = array(
                            'task_id' => $task_id,
                            'project_id' => $project_id
                        );
                    } else {
                        $response['status'] = 'error';
                        $response['message'] = t('Unable to create task');
                    }
                }
            }
        }
        // 5. Open dashboard command
        else if (preg_match('/^(?:open|show|display|go to)\s+(.+?)\s+(?:dashboard|board|project)$/i', $command, $matches)) {
            $projectName = $matches[1];
            $project = $this->projectModel->getByName($projectName);
            
            if ($project) {
                $response['action'] = 'redirect';
                $response['data'] = array(
                    'url' => $this->helper->url->to('BoardViewController', 'show', array('project_id' => $project['id']))
                );
                $response['message'] = t('Opening dashboard for project: ') . $projectName;
            } else {
                $response['status'] = 'error';
                $response['message'] = t('Project not found: ') . $projectName;
            }
        }
        // 6. Basic commands
        else {
            switch ($normalizedCommand) {
                case 'show time':
                case 'what time is it':
                case 'current time':
                case 'time please':
                case 'tell me the time':
                    $response['message'] = date('Y-m-d H:i:s');
                    break;

                default:
                    // Only set error if no other command matched
                    if ($response['status'] !== 'success') {
                        $response['status'] = 'error';
                        $response['message'] = t('Unknown command: ') . $command;
                    }
            }
        }

        // Debug: Log response before sending
        error_log("Sending response: " . json_encode($response));
        $this->response->json($response);
    }
} 