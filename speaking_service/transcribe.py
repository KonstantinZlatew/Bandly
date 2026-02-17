from openai import OpenAI
from pathlib import Path


client = OpenAI()


def transcribe_audio(audio_path: str) -> str:
    """
    Transcribes an audio file into text.
    """

    audio_file = Path(audio_path)

    if not audio_file.exists():
        raise FileNotFoundError(f"Audio file not found: {audio_path}")

    with open(audio_file, "rb") as f:
        transcript = client.audio.transcriptions.create(
            model="gpt-4o-mini-transcribe",
            file=f
        )

    return transcript.text
