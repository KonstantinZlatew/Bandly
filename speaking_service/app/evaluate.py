"""
Speaking Evaluation Module
Evaluates IELTS speaking recordings using OpenAI Audio API
"""

import os
import json
from typing import Dict, Any
from openai import OpenAI
from pathlib import Path

# Load environment variables
from dotenv import load_dotenv
load_dotenv()

# Initialize OpenAI client
api_key = os.getenv("OPENAI_API_KEY")
if not api_key:
    raise RuntimeError("Missing OPENAI_API_KEY in .env")

client = OpenAI(api_key=api_key)

# Load grading criteria
def load_grading_criteria() -> str:
    """Load grading criteria from markdown file"""
    criteria_path = Path(__file__).parent.parent / "prompt_data" / "grading_criteria.md"
    with open(criteria_path, 'r', encoding='utf-8') as f:
        return f.read()


def transcribe_audio(audio_path: str) -> str:
    """
    Transcribe audio file using OpenAI Whisper API
    
    Args:
        audio_path: Path to the audio file
        
    Returns:
        Transcribed text
    """
    with open(audio_path, 'rb') as audio_file:
        transcript = client.audio.transcriptions.create(
            model="whisper-1",
            file=audio_file,
            language="en"
        )
    return transcript.text


def evaluate_speaking(audio_path: str, task_prompt: str) -> Dict[str, Any]:
    """
    Evaluate speaking performance using OpenAI GPT-4
    
    Args:
        audio_path: Path to the audio file
        task_prompt: The IELTS speaking task prompt (cue card)
        
    Returns:
        Dictionary with evaluation results
    """
    # Load grading criteria
    grading_criteria = load_grading_criteria()
    
    # Transcribe audio
    transcript = transcribe_audio(audio_path)
    
    # Prepare system prompt with grading criteria
    system_prompt = f"""You are an expert IELTS speaking examiner. Evaluate the candidate's speaking performance based on the IELTS Speaking Band Descriptors.

GRADING CRITERIA:
{grading_criteria}

You must evaluate the candidate's performance on FOUR criteria:
1. Fluency and Coherence (FC)
2. Lexical Resource (LR)
3. Grammar (GRA)
4. Pronunciation (PR)

For each criterion, provide:
- A band score from 0.0 to 9.0 (can be half bands like 6.5, 7.5)
- Detailed feedback explaining the score

Also provide:
- An overall band score (average of the four criteria, rounded to nearest half band)
- An overall comment summarizing the performance
- An improvement plan with 3-5 specific suggestions

Return your response as a valid JSON object with this exact structure:
{{
    "overall_band": 7.5,
    "FC": 7.0,
    "LR": 8.0,
    "GRA": 7.0,
    "PR": 7.5,
    "transcript": "{transcript[:100]}...",
    "notes": {{
        "FC": "Detailed feedback on fluency and coherence",
        "LR": "Detailed feedback on lexical resource",
        "GRA": "Detailed feedback on grammar",
        "PR": "Detailed feedback on pronunciation"
    }},
    "overall_comment": "Overall assessment of the speaking performance",
    "improvement_plan": [
        "Suggestion 1",
        "Suggestion 2",
        "Suggestion 3"
    ]
}}

IMPORTANT: Return ONLY valid JSON, no additional text or markdown formatting."""

    # Prepare user message
    user_message = f"""TASK PROMPT (Cue Card):
{task_prompt}

TRANSCRIBED SPEECH:
{transcript}

Please evaluate this speaking performance according to the IELTS Speaking Band Descriptors. Consider:
- How well the candidate addressed the task prompt
- Fluency and coherence of speech
- Range and accuracy of vocabulary
- Grammatical range and accuracy
- Pronunciation and intelligibility

Return your evaluation as a JSON object with the structure specified above."""

    # Call GPT-4 for evaluation
    response = client.chat.completions.create(
        model="gpt-4o",  # Using GPT-4o for better analysis
        messages=[
            {"role": "system", "content": system_prompt},
            {"role": "user", "content": user_message}
        ],
        temperature=0.3,
        response_format={"type": "json_object"}  # Force JSON response
    )
    
    # Parse response
    result_text = response.choices[0].message.content
    
    try:
        result = json.loads(result_text)
    except json.JSONDecodeError as e:
        # If JSON parsing fails, try to extract JSON from response
        import re
        json_match = re.search(r'\{.*\}', result_text, re.DOTALL)
        if json_match:
            result = json.loads(json_match.group())
        else:
            raise ValueError(f"Failed to parse JSON response: {e}")
    
    # Add transcript to result
    result["transcript"] = transcript
    
    # Validate and ensure all required fields are present
    required_fields = ["overall_band", "FC", "LR", "GRA", "PR", "notes", "overall_comment", "improvement_plan"]
    for field in required_fields:
        if field not in result:
            if field == "notes":
                result[field] = {"FC": "", "LR": "", "GRA": "", "PR": ""}
            elif field == "improvement_plan":
                result[field] = []
            else:
                result[field] = 0.0
    
    # Ensure notes has all required sub-fields
    if "FC" not in result["notes"]:
        result["notes"]["FC"] = ""
    if "LR" not in result["notes"]:
        result["notes"]["LR"] = ""
    if "GRA" not in result["notes"]:
        result["notes"]["GRA"] = ""
    if "PR" not in result["notes"]:
        result["notes"]["PR"] = ""
    
    return result
