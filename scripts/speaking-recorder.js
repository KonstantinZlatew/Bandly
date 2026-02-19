// Speaking Recorder JavaScript
// Handles audio recording, playback, and submission

let mediaRecorder = null;
let audioChunks = [];
let audioBlob = null;
let recordingStartTime = null;
let timerInterval = null;
let isRecording = false;
let pollInterval = null;

const recordBtn = document.getElementById('recordBtn');
const stopBtn = document.getElementById('stopBtn');
const timer = document.getElementById('timer');
const timerText = document.getElementById('timerText');
const recordingStatus = document.getElementById('recordingStatus');
const audioPlayback = document.getElementById('audioPlayback');
const audioPlayer = document.getElementById('audioPlayer');
const deleteRecording = document.getElementById('deleteRecording');
const saveRecording = document.getElementById('saveRecording');
const resultsContent = document.getElementById('resultsContent');

// Check if browser supports MediaRecorder
if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
  recordingStatus.innerHTML = '<div class="error-message"><p>Your browser does not support audio recording. Please use a modern browser like Chrome, Firefox, or Edge.</p></div>';
  recordBtn.disabled = true;
}

// Record button click
recordBtn.addEventListener('click', async function() {
  try {
    // Request microphone access
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    
    // Initialize MediaRecorder
    const options = { mimeType: 'audio/webm' };
    if (!MediaRecorder.isTypeSupported(options.mimeType)) {
      options.mimeType = 'audio/webm;codecs=opus';
      if (!MediaRecorder.isTypeSupported(options.mimeType)) {
        options.mimeType = ''; // Let browser choose
      }
    }
    
    mediaRecorder = new MediaRecorder(stream, options);
    audioChunks = [];
    
    mediaRecorder.ondataavailable = function(event) {
      if (event.data.size > 0) {
        audioChunks.push(event.data);
      }
    };
    
    mediaRecorder.onstop = function() {
      audioBlob = new Blob(audioChunks, { type: mediaRecorder.mimeType || 'audio/webm' });
      const audioUrl = URL.createObjectURL(audioBlob);
      audioPlayer.src = audioUrl;
      audioPlayback.style.display = 'block';
      
      // Stop all tracks
      stream.getTracks().forEach(track => track.stop());
      
      recordingStatus.innerHTML = '<div class="recording-status stopped">Recording stopped. You can play it back or record again.</div>';
    };
    
    // Start recording
    mediaRecorder.start();
    isRecording = true;
    recordingStartTime = Date.now();
    
    // Update UI
    recordBtn.style.display = 'none';
    stopBtn.style.display = 'inline-flex';
    timer.style.display = 'block';
    recordingStatus.innerHTML = '<div class="recording-status recording">Recording in progress...</div>';
    recordBtn.classList.add('recording');
    
    // Start timer
    startTimer();
    
  } catch (error) {
    console.error('Error accessing microphone:', error);
    recordingStatus.innerHTML = '<div class="error-message"><p>Error accessing microphone: ' + error.message + '</p></div>';
  }
});

// Stop button click
stopBtn.addEventListener('click', function() {
  if (mediaRecorder && isRecording) {
    mediaRecorder.stop();
    isRecording = false;
    stopTimer();
    
    // Update UI
    recordBtn.style.display = 'inline-flex';
    stopBtn.style.display = 'none';
    timer.style.display = 'none';
    recordBtn.classList.remove('recording');
  }
});

// Timer function
function startTimer() {
  timerInterval = setInterval(function() {
    if (recordingStartTime) {
      const elapsed = Math.floor((Date.now() - recordingStartTime) / 1000);
      const minutes = Math.floor(elapsed / 60);
      const seconds = elapsed % 60;
      timerText.textContent = 
        String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
      
      // Auto-stop at 2 minutes (120 seconds)
      if (elapsed >= 120) {
        if (mediaRecorder && isRecording) {
          mediaRecorder.stop();
          isRecording = false;
          stopTimer();
          recordBtn.style.display = 'inline-flex';
          stopBtn.style.display = 'none';
          timer.style.display = 'none';
          recordBtn.classList.remove('recording');
          recordingStatus.innerHTML = '<div class="recording-status stopped">Recording stopped automatically at 2 minutes.</div>';
        }
      }
    }
  }, 1000);
}

function stopTimer() {
  if (timerInterval) {
    clearInterval(timerInterval);
    timerInterval = null;
  }
}

// Delete recording
deleteRecording.addEventListener('click', function() {
  audioBlob = null;
  audioChunks = [];
  audioPlayer.src = '';
  audioPlayback.style.display = 'none';
  recordingStatus.innerHTML = '';
  recordBtn.style.display = 'inline-flex';
  stopBtn.style.display = 'none';
  timer.style.display = 'none';
  recordBtn.classList.remove('recording');
  stopTimer();
});

// Save recording
saveRecording.addEventListener('click', async function() {
  if (!audioBlob) {
    alert('No recording to save. Please record first.');
    return;
  }
  
  // Check entitlements
  if (!entitlementInfo || !entitlementInfo.can_analyze) {
    alert(entitlementInfo.reason || 'You do not have permission to analyze recordings. Please purchase credits or a subscription.');
    window.location.href = 'payment.php';
    return;
  }
  
  const taskId = document.getElementById('taskId')?.value;
  const examVariantId = document.getElementById('examVariantId')?.value;
  const taskType = document.getElementById('taskType')?.value;
  const taskPrompt = document.getElementById('taskPrompt')?.value;
  
  if (!taskId || !examVariantId) {
    alert('Error: Task information missing. Please refresh the page.');
    return;
  }
  
  // Disable button and show loading
  const btn = this;
  btn.disabled = true;
  btn.textContent = 'Saving...';
  
  // Show processing message
  resultsContent.innerHTML = '<div class="processing-message"><p>Saving your recording...</p></div>';
  
  try {
    // Prepare form data
    const formData = new FormData();
    formData.append('task_id', taskId);
    formData.append('task_type', taskType);
    formData.append('task_prompt', taskPrompt);
    formData.append('exam_variant_id', examVariantId);
    
    // Create audio file
    const audioFile = new File([audioBlob], `speaking_${Date.now()}.webm`, {
      type: audioBlob.type || 'audio/webm'
    });
    formData.append('audio', audioFile);
    
    // Send to API
    const response = await fetch('api/speaking-submission-save.php', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.ok && result.submission_id) {
      // Update UI immediately
      btn.textContent = 'Analyzing...';
      resultsContent.innerHTML = '<div class="processing-message"><p>Recording saved successfully! Starting analysis...</p></div>';
      
      // Check service status first
      try {
        const serviceCheck = await fetch('api/speaking-service-check.php');
        const serviceStatus = await serviceCheck.json();
        if (serviceStatus.checks) {
          if (!serviceStatus.checks.python_service) {
            resultsContent.innerHTML = 
              `<div class="error-message">
                <p><strong>Python service is not running!</strong></p>
                <p>Please start the speaking service:</p>
                <code style="display: block; margin: 10px 0; padding: 10px; background: #f5f5f5; border-radius: 4px;">
                  cd speaking_service<br>
                  python -m uvicorn app.main:app --port 8001
                </code>
                <p>Error: ${serviceStatus.checks.python_service_error || 'Unknown'}</p>
              </div>`;
            btn.disabled = false;
            btn.textContent = 'Save Recording';
            return;
          }
          if (!serviceStatus.checks.worker_running && serviceStatus.checks.pending_jobs > 0) {
            resultsContent.innerHTML = 
              `<div class="warning-message" style="padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px; color: #856404;">
                <p><strong>Worker is not running!</strong></p>
                <p>Please start the speaking worker:</p>
                <code style="display: block; margin: 10px 0; padding: 10px; background: #f5f5f5; border-radius: 4px;">
                  php speaking-worker.php --daemon
                </code>
                <p>Pending jobs: ${serviceStatus.checks.pending_jobs}</p>
              </div>`;
          }
        }
      } catch (e) {
        console.error('Service check error:', e);
      }
      
      // Start polling for analysis status
      startPolling(result.submission_id);
      
      // Reset recording UI (but keep button disabled while analyzing)
      audioBlob = null;
      audioChunks = [];
      audioPlayer.src = '';
      audioPlayback.style.display = 'none';
      
    } else {
      alert('Error saving recording: ' + (result.error || 'Unknown error'));
      btn.disabled = false;
      btn.textContent = 'Save Recording';
      resultsContent.innerHTML = '<div class="results-placeholder"><p>Record your response to see analysis results here.</p></div>';
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error saving recording. Please try again.');
    btn.disabled = false;
    btn.textContent = 'Save Recording';
    resultsContent.innerHTML = '<div class="results-placeholder"><p>Record your response to see analysis results here.</p></div>';
  }
});

// Poll for submission status
function pollSubmissionStatus(submissionId) {
  return fetch(`api/speaking-status.php?id=${submissionId}`)
    .then(res => res.json())
    .then(data => {
      if (!data.ok) {
        throw new Error(data.error || 'Failed to check status');
      }
      return data.submission;
    });
}

function displayAnalysisResult(result) {
  const resultsContent = document.getElementById('resultsContent');
  
  if (!result) {
    resultsContent.innerHTML = '<div class="results-placeholder"><p>No results available</p></div>';
    return;
  }
  
  let html = '<div class="analysis-result">';
  
  // Overall Band Score
  html += `<div class="score-section">
    <h3>Overall Band Score</h3>
    <div class="band-score">${result.overall_band || 'N/A'}</div>
  </div>`;
  
  // Individual Scores (FC, LR, GRA, PR for speaking)
  html += '<div class="criteria-scores">';
  html += `<div class="score-item"><strong>FC:</strong> ${result.FC || 'N/A'}</div>`;
  html += `<div class="score-item"><strong>LR:</strong> ${result.LR || 'N/A'}</div>`;
  html += `<div class="score-item"><strong>GRA:</strong> ${result.GRA || 'N/A'}</div>`;
  html += `<div class="score-item"><strong>PR:</strong> ${result.PR || 'N/A'}</div>`;
  html += '</div>';
  
  // Transcript
  if (result.transcript) {
    html += '<div class="notes-section"><h3>Transcript</h3>';
    html += `<p style="line-height: 1.6; color: #666;">${result.transcript}</p>`;
    html += '</div>';
  }
  
  // Notes
  if (result.notes) {
    html += '<div class="notes-section"><h3>Detailed Feedback</h3>';
    if (result.notes.FC) html += `<p><strong>FC (Fluency and Coherence):</strong> ${result.notes.FC}</p>`;
    if (result.notes.LR) html += `<p><strong>LR (Lexical Resource):</strong> ${result.notes.LR}</p>`;
    if (result.notes.GRA) html += `<p><strong>GRA (Grammar):</strong> ${result.notes.GRA}</p>`;
    if (result.notes.PR) html += `<p><strong>PR (Pronunciation):</strong> ${result.notes.PR}</p>`;
    html += '</div>';
  }
  
  // Overall Comment
  if (result.overall_comment) {
    html += `<div class="comment-section"><h3>Overall Comment</h3><p>${result.overall_comment}</p></div>`;
  }
  
  // Improvement Plan
  if (result.improvement_plan && Array.isArray(result.improvement_plan)) {
    html += '<div class="improvement-section"><h3>Improvement Plan</h3><ul>';
    result.improvement_plan.forEach(item => {
      html += `<li>${item}</li>`;
    });
    html += '</ul></div>';
  }
  
  html += '</div>';
  resultsContent.innerHTML = html;
}

function startPolling(submissionId) {
  // Clear any existing polling
  if (pollInterval) {
    clearInterval(pollInterval);
  }
  
  // Do immediate check first
  checkStatusOnce(submissionId);
  
  // Poll every 2 seconds
  pollInterval = setInterval(() => {
    checkStatusOnce(submissionId);
  }, 2000);
}

async function checkStatusOnce(submissionId) {
  try {
    const submission = await pollSubmissionStatus(submissionId);
    
    if (submission.status === 'done') {
      clearInterval(pollInterval);
      pollInterval = null;
      displayAnalysisResult(submission.analysis_result);
      const btn = document.getElementById('saveRecording');
      btn.disabled = false;
      btn.textContent = 'Save Recording';
    } else if (submission.status === 'failed') {
      clearInterval(pollInterval);
      pollInterval = null;
      resultsContent.innerHTML = 
        `<div class="error-message"><p>Analysis failed: ${submission.error_message || 'Unknown error'}</p></div>`;
      const btn = document.getElementById('saveRecording');
      btn.disabled = false;
      btn.textContent = 'Save Recording';
    } else if (submission.status === 'processing') {
      resultsContent.innerHTML = 
        '<div class="processing-message"><p>Processing your recording... Please wait.</p></div>';
    } else if (submission.status === 'pending') {
      resultsContent.innerHTML = 
        '<div class="processing-message"><p>Recording saved. Waiting for worker to process... (Status: pending)</p><p style="font-size: 12px; color: #666; margin-top: 10px;">Note: Make sure the speaking-worker.php is running.</p></div>';
    }
  } catch (error) {
    console.error('Polling error:', error);
    // Show error but keep polling
    resultsContent.innerHTML = 
      `<div class="error-message"><p>Error checking status: ${error.message}</p><p style="font-size: 12px;">Still polling...</p></div>`;
  }
}
