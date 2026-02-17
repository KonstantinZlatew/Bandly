import os
import json
from dotenv import load_dotenv

from transcribe import transcribe_audio
from grading import load_criteria, grade_speech


load_dotenv()


def main():
    audio_path = "sample_audio.wav"
    criteria_path = "rag_data/grading_criteria.txt"

    # Step 1: Transcribe
    transcript = transcribe_audio(audio_path)

    # Save transcript
    os.makedirs("outputs", exist_ok=True)
    with open("outputs/transcript.txt", "w", encoding="utf-8") as f:
        f.write(transcript)

    # Step 2: Load grading criteria
    criteria = load_criteria(criteria_path)

    # Step 3: Grade speech
    grade_result = grade_speech(transcript, criteria)

    # Save grade
    with open("outputs/grade.json", "w", encoding="utf-8") as f:
        json.dump(grade_result, f, indent=4)

    print("Grading complete.")
    print(json.dumps(grade_result, indent=4))


if __name__ == "__main__":
    main()
