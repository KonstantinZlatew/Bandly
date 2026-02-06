# Image Upload Support for Academic Task 1

The AI service now supports image uploads for `academic_task_1` evaluations. This allows the system to process charts, graphs, diagrams, and other visual data that are typically part of IELTS Academic Writing Task 1.

## Supported Image Formats

- JPEG/JPG
- PNG
- GIF
- WebP

## API Usage

### Endpoint
`POST /evaluate`

### Parameters (Form Data)

- `task_type` (required): Must be `"academic_task_1"` for image support
- `task_prompt` (required): The task prompt/question
- `essay` (required): The candidate's written response
- `image` (optional): Image file upload (multipart/form-data)
- `image_url` (optional): URL to an image (not yet implemented)
- `image_base64` (optional): Base64 encoded image data

### Example: Using File Upload

```python
import requests

url = "http://localhost:8000/evaluate"
files = {
    'image': open('chart.png', 'rb')
}
data = {
    'task_type': 'academic_task_1',
    'task_prompt': 'The chart below shows...',
    'essay': 'The graph illustrates...'
}

response = requests.post(url, files=files, data=data)
```

### Example: Using Base64

```python
import requests
import base64

with open('chart.png', 'rb') as f:
    image_base64 = base64.b64encode(f.read()).decode('utf-8')

data = {
    'task_type': 'academic_task_1',
    'task_prompt': 'The chart below shows...',
    'essay': 'The graph illustrates...',
    'image_base64': image_base64
}

response = requests.post(url, data=data)
```

## Response

The response includes an `image_analysis` field when an image is provided:

```json
{
    "overall_band": 7.0,
    "TR": 7.0,
    "CC": 7.0,
    "LR": 7.0,
    "GRA": 7.0,
    "notes": {...},
    "overall_comment": "...",
    "improvement_plan": [...],
    "word_count": 180,
    "used_rag": true,
    "image_analysis": {
        "image_provided": true,
        "analysis_status": "pending"
    }
}
```

## Image Analysis

Currently, the image analysis functionality is a placeholder. The `analyze_image_with_ai()` function in `app/image_analysis.py` will be implemented to:

1. Analyze charts, graphs, and diagrams
2. Extract data points and trends
3. Identify visual elements
4. Provide structured data about the image

This data will then be used to better evaluate the candidate's Task Achievement (TR) score by comparing their description with the actual visual data.

## Vision Model Support

If using a vision-capable OpenAI model (e.g., `gpt-4o`, `gpt-4o-mini`), the image will be sent directly to the model for analysis. Set the `GRADE_MODEL` environment variable to use a vision model.

## Notes

- Images are only processed for `academic_task_1` task type
- For other task types (`general_task_1`, `task_2`), image parameters are ignored
- Image analysis API integration is pending implementation

