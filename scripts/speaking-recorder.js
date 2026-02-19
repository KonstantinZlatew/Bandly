// Speaking Recorder JavaScript
// Handles audio recording, playback, and submission

let mediaRecorder = null;
let audioChunks = [];
let audioBlob = null;
let recordingStartTime = null;
let timerInterval = null;
let isRecording = false;

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
      // Show success message
      resultsContent.innerHTML = `
        <div class="recording-status stopped">
          <p><strong>Recording saved successfully!</strong></p>
          <p>Submission ID: ${result.submission_id}</p>
          <p>AI analysis will be available soon. This feature is coming in a future update.</p>
        </div>
      `;
      
      // Mark task as seen
      // This will be handled by the API endpoint
      
      // Reset UI
      btn.disabled = false;
      btn.textContent = 'Save Recording';
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
