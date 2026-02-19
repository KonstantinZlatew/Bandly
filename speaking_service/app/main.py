"""
FastAPI Application for IELTS Speaking Evaluation Service
"""

import os
from pathlib import Path
from typing import Optional
from fastapi import FastAPI, HTTPException, File, UploadFile, Form
from fastapi.responses import JSONResponse
from fastapi.middleware.cors import CORSMiddleware
from dotenv import load_dotenv

from app.evaluate import evaluate_speaking

# Load environment variables
load_dotenv()

# Initialize FastAPI app
app = FastAPI(title="IELTS Speaking Evaluation Service")

# Add CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # In production, specify allowed origins
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Validate API key
api_key = os.getenv("OPENAI_API_KEY")
if not api_key:
    raise RuntimeError("Missing OPENAI_API_KEY in .env")


@app.get("/")
async def root():
    """Health check endpoint"""
    return {"status": "ok", "service": "IELTS Speaking Evaluation Service"}


@app.post("/evaluate")
async def evaluate(
    task_prompt: str = Form(...),
    audio_path: Optional[str] = Form(None),
    audio: Optional[UploadFile] = File(None)
):
    """
    Evaluate a speaking recording
    
    Args:
        task_prompt: The IELTS speaking task prompt (cue card)
        audio_path: Optional path to audio file (if already on server)
        audio: Optional audio file upload (if audio_path not provided)
        
    Returns:
        JSON with evaluation results
    """
    try:
        # If audio_path is provided, use it; otherwise use uploaded file
        if audio_path:
            # Use provided path (file already on server)
            if not os.path.exists(audio_path):
                raise HTTPException(status_code=400, detail=f"Audio file not found: {audio_path}")
            temp_audio_path = audio_path
            cleanup_file = False
        elif audio:
            # Save uploaded file temporarily
            upload_dir = Path("/tmp/speaking_uploads")
            upload_dir.mkdir(exist_ok=True)
            
            temp_audio_path = upload_dir / f"temp_{audio.filename}"
            with open(temp_audio_path, "wb") as f:
                content = await audio.read()
                f.write(content)
            cleanup_file = True
        else:
            raise HTTPException(status_code=400, detail="Either audio_path or audio file must be provided")
        
        try:
            # Validate task prompt
            if not task_prompt or not task_prompt.strip():
                raise HTTPException(status_code=400, detail="Task prompt is required")
            
            # Evaluate speaking
            result = evaluate_speaking(str(temp_audio_path), task_prompt)
            
            return JSONResponse(content={
                "ok": True,
                "result": result
            })
            
        finally:
            # Clean up temporary file if we created it
            if cleanup_file and os.path.exists(temp_audio_path):
                try:
                    os.remove(temp_audio_path)
                except Exception:
                    pass  # Ignore cleanup errors
                    
    except HTTPException:
        raise
    except Exception as e:
        import traceback
        error_detail = str(e)
        traceback.print_exc()
        raise HTTPException(
            status_code=500,
            detail=f"Error evaluating speaking: {error_detail}"
        )


@app.get("/health")
async def health():
    """Health check endpoint"""
    return {"status": "healthy"}


if __name__ == "__main__":
    import uvicorn
    port = int(os.getenv("PORT", "8001"))
    uvicorn.run(app, host="0.0.0.0", port=port)
