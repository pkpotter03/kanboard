(function() {
    "use strict";
    
    KB.component('voice-commands', function(containerElement) {
        console.log('Voice commands component initializing...'); // Debug log

        var startBtn = document.getElementById('startVoiceBtn');
        var stopBtn = document.getElementById('stopVoiceBtn');
        var statusDiv = document.getElementById('voiceStatus');
        var recognition = null;
        var isListening = false;

        // Get the CSRF token
        var csrfToken = KB.token; // Get token from KB object
        console.log('CSRF Token available:', !!csrfToken); // Debug log

        // Retry getting token if not available
        if (!csrfToken) {
            var metaToken = document.querySelector('meta[name="csrf-token"]');
            if (metaToken) {
                csrfToken = metaToken.getAttribute('content');
                KB.token = csrfToken; // Set it in KB object for other components
                console.log('CSRF Token retrieved from meta:', !!csrfToken);
            }
        }

        console.log('Buttons found:', { // Debug log
            startBtn: !!startBtn,
            stopBtn: !!stopBtn,
            statusDiv: !!statusDiv
        });

        function showStatus(message, isError) {
            console.log('Status:', message, 'Error:', isError); // Debug log
            if (!statusDiv) {
                console.error('Status div not found!');
                return;
            }
            statusDiv.textContent = message;
            statusDiv.classList.remove('hidden');
            statusDiv.classList.toggle('error', isError === true);
            
            // Hide status after 3 seconds
            setTimeout(function() {
                statusDiv.classList.add('hidden');
            }, 3000);
        }

        function getProjectContext() {
            // Try multiple ways to get project ID
            var projectId = 0;
            
            // 1. Try from KB object
            if (typeof KB !== 'undefined' && KB.projectId) {
                projectId = KB.projectId;
            }
            
            // 2. Try from meta tag
            if (!projectId) {
                var metaProject = document.querySelector('meta[name="project-id"]');
                if (metaProject) {
                    projectId = parseInt(metaProject.getAttribute('content'), 10);
                }
            }
            
            // 3. Try from URL
            if (!projectId) {
                var urlParams = new URLSearchParams(window.location.search);
                projectId = parseInt(urlParams.get('project_id'), 10) || 0;
            }
            
            // 4. Try from data attribute on body
            if (!projectId) {
                var bodyProject = document.body.getAttribute('data-project-id');
                if (bodyProject) {
                    projectId = parseInt(bodyProject, 10);
                }
            }
            
            return projectId;
        }

        function processVoiceCommand(command) {
            console.log('Processing command:', command);
            
            // Get current project ID from the page context
            var projectId = getProjectContext();
            var taskId = KB.taskId || document.querySelector('meta[name="task-id"]')?.content || 0;

            // Log the context
            console.log('Context - Project ID:', projectId, 'Task ID:', taskId);

            // If command requires project context but none is selected
            if (command.toLowerCase().includes('create task') && !projectId) {
                // Try to get list of projects
                KB.http.get('?controller=ProjectListController&action=show')
                    .success(function(response) {
                        if (response && response.projects && response.projects.length > 0) {
                            // Show project selection dialog
                            showProjectSelector(response.projects, command);
                        } else {
                            showStatus('Please select a project first', true);
                        }
                    })
                    .error(function() {
                        showStatus('Please select a project first', true);
                    });
                return;
            }

            // Log the exact request payload
            var payload = {
                command: command,
                project_id: projectId,
                task_id: taskId,
                csrf_token: csrfToken
            };
            console.log('Sending request with payload:', payload);

            KB.http.postJson(
                '?controller=VoiceCommandController&action=process', 
                payload
            ).success(function(response) {
                console.log('Raw server response:', response);
                if (response.status === 'success') {
                    if (response.message) {
                        showStatus(response.message);
                    }
                    
                    if (response.action === 'openModal' && response.data.url) {
                        KB.modal.open(response.data.url, 'large');
                    } else if (response.action === 'reload') {
                        window.location.reload();
                    } else if (response.action === 'redirect' && response.data.url) {
                        window.location.href = response.data.url;
                    } else if (response.action === 'alert') {
                        alert(response.message);
                    }
                } else {
                    showStatus(response.message || 'Error processing command', true);
                }
            }).error(function(error) {
                console.error('Detailed error:', error);
                if (error.message === 'Access Forbidden') {
                    showStatus('Security token expired. Please refresh the page.', true);
                } else {
                    showStatus('Error communicating with server', true);
                }
            });
        }

        function showProjectSelector(projects, command) {
            var modalContent = '<div class="project-selector">';
            modalContent += '<h2>Select a project</h2>';
            modalContent += '<p>Please select a project to create the task in:</p>';
            modalContent += '<select id="voice-project-select" class="project-select">';
            
            projects.forEach(function(project) {
                modalContent += '<option value="' + project.id + '">' + project.name + '</option>';
            });
            
            modalContent += '</select>';
            modalContent += '<button class="btn btn-blue" onclick="executeCommandWithProject(\'' + command + '\')">Continue</button>';
            modalContent += '</div>';
            
            KB.modal.open(modalContent, 'medium');
        }

        function executeCommandWithProject(command) {
            var select = document.getElementById('voice-project-select');
            if (select) {
                var projectId = select.value;
                KB.projectId = projectId; // Store for future use
                KB.modal.close();
                
                // Re-process the command with the selected project
                var payload = {
                    command: command,
                    project_id: projectId,
                    csrf_token: csrfToken
                };
                
                KB.http.postJson(
                    '?controller=VoiceCommandController&action=process', 
                    payload
                ).success(function(response) {
                    handleCommandResponse(response);
                }).error(function(error) {
                    showStatus('Error executing command', true);
                });
            }
        }

        function initSpeechRecognition() {
            console.log('Initializing speech recognition...'); // Debug log
            
            if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
                console.error('Speech recognition not supported'); // Debug log
                showStatus('Speech recognition is not supported in this browser', true);
                if (startBtn) startBtn.disabled = true;
                return;
            }

            try {
                recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
                recognition.continuous = false;
                recognition.interimResults = false;
                recognition.lang = 'en-US';

                recognition.onstart = function() {
                    console.log('Recognition started'); // Debug log
                    isListening = true;
                    if (startBtn) startBtn.classList.add('hidden');
                    if (stopBtn) stopBtn.classList.remove('hidden');
                    showStatus('Listening...');
                };

                recognition.onend = function() {
                    console.log('Recognition ended'); // Debug log
                    isListening = false;
                    if (startBtn) startBtn.classList.remove('hidden');
                    if (stopBtn) stopBtn.classList.add('hidden');
                    if (statusDiv) statusDiv.classList.add('hidden');
                };

                recognition.onresult = function(event) {
                    var command = event.results[event.results.length - 1][0].transcript.trim();
                    console.log('Recognition result:', command); // Debug log
                    showStatus('Command: ' + command);
                    processVoiceCommand(command);
                };

                recognition.onerror = function(event) {
                    console.error('Recognition error:', event.error); // Debug log
                    showStatus('Error: ' + event.error, true);
                    stopVoiceRecognition();
                };

                console.log('Speech recognition initialized successfully'); // Debug log
            } catch (e) {
                console.error('Error initializing speech recognition:', e); // Debug log
                showStatus('Error initializing speech recognition', true);
            }
        }

        function startVoiceRecognition() {
            console.log('Starting voice recognition...'); // Debug log
            if (!recognition) {
                initSpeechRecognition();
            }
            
            if (recognition && !isListening) {
                try {
                    recognition.start();
                    console.log('Recognition started successfully'); // Debug log
                } catch (e) {
                    console.error('Error starting recognition:', e); // Debug log
                    recognition.stop();
                    setTimeout(function() {
                        try {
                            recognition.start();
                            console.log('Recognition restarted successfully'); // Debug log
                        } catch (err) {
                            console.error('Failed to restart recognition:', err); // Debug log
                            showStatus('Error starting voice recognition', true);
                        }
                    }, 100);
                }
            }
        }

        function stopVoiceRecognition() {
            console.log('Stopping voice recognition...'); // Debug log
            if (recognition && isListening) {
                try {
                    recognition.stop();
                    isListening = false;
                    if (startBtn) startBtn.classList.remove('hidden');
                    if (stopBtn) stopBtn.classList.add('hidden');
                    if (statusDiv) statusDiv.classList.add('hidden');
                    console.log('Recognition stopped successfully'); // Debug log
                } catch (e) {
                    console.error('Error stopping recognition:', e); // Debug log
                    showStatus('Error stopping voice recognition', true);
                }
            }
        }

        // Add event listeners for voice command buttons
        if (startBtn && stopBtn) {
            console.log('Adding button event listeners...'); // Debug log
            
            startBtn.addEventListener('click', function(e) {
                console.log('Start button clicked'); // Debug log
                e.preventDefault();
                if (isListening) {
                    stopVoiceRecognition();
                } else {
                    startVoiceRecognition();
                }
            });

            stopBtn.addEventListener('click', function(e) {
                console.log('Stop button clicked'); // Debug log
                e.preventDefault();
                stopVoiceRecognition();
            });

            // Initialize speech recognition
            initSpeechRecognition();
        } else {
            console.error('Voice command buttons not found!'); // Debug log
        }

        // This is called when the component is rendered
        this.render = function() {
            console.log('Voice commands component rendered');
            if (!csrfToken) {
                csrfToken = KB.token;
                console.log('CSRF Token refreshed:', !!csrfToken);
            }
        };
    });

    // Register the component for auto-initialization
    KB.on('dom.ready', function() {
        KB.render();
    });
})(); 