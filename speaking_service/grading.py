from openai import OpenAI
import json


client = OpenAI()


def load_criteria(path: str) -> str:
    """
    Loads grading criteria from text file.
    """

    with open(path, "r", encoding="utf-8") as f:
        return f.read()


def grade_speech(transcript: str, criteria: str) -> dict:
    """
    Grades a speech transcript using provided criteria.
    Returns structured JSON.
    """

    system_prompt = f"""
You are an objective speech examiner.

Use the following grading criteria strictly:

{criteria}

Return the result in JSON format:
{{
  "content": int,
  "clarity": int,
  "structure": int,
  "vocabulary": int,
  "total": int,
  "feedback": "string"
}}
"""

    user_prompt = f"""
Speech Transcript:

{transcript}
"""

    response = client.chat.completions.create(
        model="gpt-4o-mini",
        messages=[
            {"role": "system", "content": system_prompt},
            {"role": "user", "content": user_prompt}
        ],
        temperature=0
    )

    result = response.choices[0].message.content

    return json.loads(result)
