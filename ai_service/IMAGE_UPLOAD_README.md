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

The image analysis functionality is now **fully implemented**. The `analyze_image_with_ai()` function in `app/image_analysis.py` uses OpenAI's vision API to:

1. **Analyze charts, graphs, and diagrams** - Identifies the type of visual (bar chart, line graph, pie chart, table, diagram, map, process, etc.)
2. **Extract data points and trends** - Captures specific numerical data, percentages, dates, categories, and values
3. **Identify visual elements** - Detects key features, patterns, and relationships in the data
4. **Provide structured data** - Returns organized information about the image content

This analysis is then used to **objectively evaluate** the candidate's Task Achievement (TR) score by:
- Comparing what the candidate wrote against what is actually shown in the image
- Verifying accuracy of data points, numbers, percentages, and values mentioned
- Checking if key features are identified and described correctly
- Assessing if trends and patterns are correctly interpreted
- Deducting points for inaccuracies, missing key features, or misinterpretations

### How It Works

1. When an image is provided for `academic_task_1`, it is sent to OpenAI's vision API for analysis
2. The AI extracts structured information about the visual content
3. This analysis is included in the grading prompt to help the evaluator compare the candidate's response
4. The TR (Task Achievement) score is then determined based on how accurately the candidate described the visual data

## Environment Variables

To use image analysis, you need to set the following environment variables in your `.env` file:

- **`OPENAI_API_KEY`** (required): Your OpenAI API key for accessing the vision API
- **`VISION_MODEL`** (optional): The OpenAI model to use for image analysis. Defaults to `gpt-4o-mini` if not set. Supported models:
  - `gpt-4o` - Most capable vision model
  - `gpt-4o-mini` - Faster and cheaper, good quality (default)
  - `gpt-4-vision-preview` - Legacy vision model

- **`GRADE_MODEL`** (optional): The model used for essay grading. Defaults to `gpt-4o-mini`. If this is a vision-capable model, the image will also be included directly in the grading prompt.

### Example `.env` file:

```env
OPENAI_API_KEY=sk-your-api-key-here
VISION_MODEL=gpt-4o-mini
GRADE_MODEL=gpt-4o-mini
```

## Response with Image Analysis

When an image is provided, the response includes detailed `image_analysis` information:

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
        "analysis_status": "completed",
        "visual_type": ["Bar chart showing..."],
        "key_features_identified": ["Feature 1", "Feature 2", ...],
        "data_points_extracted": true,
        "description": "Detailed analysis of the image...",
        "description_preview": "Truncated description if too long..."
    }
}
```

## Notes

- Images are only processed for `academic_task_1` task type
- For other task types (`general_task_1`, `task_2`), image parameters are ignored
- Image analysis requires a valid `OPENAI_API_KEY` in your `.env` file
- The analysis helps objectify the TR scoring by providing factual data about what's in the image

