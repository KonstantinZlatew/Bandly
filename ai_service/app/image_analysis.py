"""
Image analysis module for academic_task_1.
This module handles image processing and analysis for IELTS Writing Task 1 (Academic).
"""

import base64
import json
from typing import Optional, Dict, Any, Tuple
import os
from openai import OpenAI
from dotenv import load_dotenv

load_dotenv()


def analyze_image_with_ai(
    image_data: bytes,
    image_format: str = "jpeg",
    api_endpoint: Optional[str] = None
) -> Dict[str, Any]:
    """
    Analyze an image using OpenAI's vision API.
    
    This function analyzes charts, graphs, diagrams, and other visual data
    typically found in IELTS Academic Writing Task 1. It extracts structured
    information about the visual elements, data points, trends, and key features.
    
    Args:
        image_data: Raw image bytes
        image_format: Format of the image (jpeg, png, etc.)
        api_endpoint: Optional API endpoint URL (not used, kept for compatibility)
        
    Returns:
        Dictionary containing analysis results with:
        - image_received: Boolean indicating if image was processed
        - image_format: Format of the image
        - analysis_status: Status of the analysis ("completed" or "error")
        - description: Detailed description of the visual content
        - extracted_data: Structured data extracted from charts/graphs
        - visual_elements: List of detected visual elements (charts, graphs, etc.)
        - key_features: Key features and trends identified
        - data_points: Specific data points extracted (if applicable)
    """
    try:
        # Get OpenAI API key
        api_key = os.getenv("OPENAI_API_KEY")
        if not api_key:
            return {
                "image_received": True,
                "image_format": image_format,
                "image_size_bytes": len(image_data),
                "analysis_status": "error",
                "description": "OpenAI API key not found. Please set OPENAI_API_KEY in .env file.",
                "extracted_data": None,
                "visual_elements": None,
                "key_features": None,
                "data_points": None,
            }
        
        # Create OpenAI client
        client = OpenAI(api_key=api_key)
        
        # Get model from environment (default to gpt-4o-mini which supports vision)
        vision_model = os.getenv("VISION_MODEL", "gpt-4o-mini")
        
        # Encode image to base64
        base64_image = encode_image_to_base64(image_data, image_format)
        
        # Prepare the analysis prompt
        analysis_prompt = """Analyze this image which is part of an IELTS Academic Writing Task 1. 
Extract and structure the following information:

1. **Visual Type**: Identify what type of visual it is (bar chart, line graph, pie chart, table, diagram, map, process, etc.)

2. **Key Data Points**: Extract specific numerical data, percentages, dates, categories, and values shown in the visual. Be precise and accurate.

3. **Trends and Patterns**: Identify any trends, patterns, comparisons, or relationships visible in the data (e.g., increases, decreases, fluctuations, highest/lowest values).

4. **Key Features**: List the most important features that should be mentioned in a good Task 1 response (typically 3-5 key features).

5. **Structure**: Describe the organization of the visual (axes labels, units, time periods, categories, etc.).

6. **Overall Summary**: Provide a brief summary of what the visual shows.

Return your analysis in a clear, structured format that can be used to evaluate whether a candidate's written response accurately describes the visual data."""

        # Call OpenAI Vision API
        response = client.chat.completions.create(
            model=vision_model,
            messages=[
                {
                    "role": "user",
                    "content": [
                        {
                            "type": "text",
                            "text": analysis_prompt
                        },
                        {
                            "type": "image_url",
                            "image_url": {
                                "url": base64_image
                            }
                        }
                    ]
                }
            ],
            temperature=0.1,  # Low temperature for more consistent, factual analysis
            max_tokens=2000,  # Allow enough tokens for detailed analysis
        )
        
        # Extract the analysis text
        analysis_text = response.choices[0].message.content.strip()
        
        # Try to parse structured data from the response
        # The model should provide structured information, but we'll also keep the raw text
        extracted_data = None
        visual_elements = None
        key_features = None
        data_points = None
        
        # Attempt to extract structured information from the response
        # This is a simple extraction - in production, you might want to ask the model
        # to return JSON for easier parsing
        lines = analysis_text.split('\n')
        current_section = None
        structured_info = {}
        
        for line in lines:
            line_lower = line.lower().strip()
            if 'visual type' in line_lower or 'type of visual' in line_lower:
                current_section = 'visual_type'
            elif 'key data points' in line_lower or 'data points' in line_lower:
                current_section = 'data_points'
            elif 'trends' in line_lower or 'patterns' in line_lower:
                current_section = 'trends'
            elif 'key features' in line_lower or 'important features' in line_lower:
                current_section = 'key_features'
            elif 'structure' in line_lower:
                current_section = 'structure'
            elif 'summary' in line_lower:
                current_section = 'summary'
            
            if current_section and line.strip() and not line.strip().startswith('#'):
                if current_section not in structured_info:
                    structured_info[current_section] = []
                structured_info[current_section].append(line.strip())
        
        # Extract specific fields
        if 'visual_type' in structured_info:
            visual_elements = structured_info['visual_type']
        if 'data_points' in structured_info:
            data_points = structured_info['data_points']
        if 'key_features' in structured_info:
            key_features = structured_info['key_features']
        if structured_info:
            extracted_data = structured_info
        
        return {
            "image_received": True,
            "image_format": image_format,
            "image_size_bytes": len(image_data),
            "analysis_status": "completed",
            "description": analysis_text,
            "extracted_data": extracted_data,
            "visual_elements": visual_elements,
            "key_features": key_features,
            "data_points": data_points,
        }
        
    except Exception as e:
        # Return error information
        return {
            "image_received": True,
            "image_format": image_format,
            "image_size_bytes": len(image_data),
            "analysis_status": "error",
            "description": f"Error analyzing image: {str(e)}",
            "extracted_data": None,
            "visual_elements": None,
            "key_features": None,
            "data_points": None,
        }


def encode_image_to_base64(image_data: bytes, image_format: str = "jpeg") -> str:
    """
    Encode image bytes to base64 string for API transmission.
    
    Args:
        image_data: Raw image bytes
        image_format: Format of the image (jpeg, png, etc.)
        
    Returns:
        Base64 encoded string with data URI prefix
    """
    base64_image = base64.b64encode(image_data).decode('utf-8')
    mime_type = f"image/{image_format}"
    return f"data:{mime_type};base64,{base64_image}"


def validate_image_format(image_data: bytes, filename: Optional[str] = None) -> Tuple[bool, str]:
    """
    Validate that the image is in a supported format.
    
    Args:
        image_data: Raw image bytes
        filename: Optional filename to check extension
        
    Returns:
        Tuple of (is_valid, format_name)
    """
    # Check file signature (magic bytes)
    if image_data.startswith(b'\xff\xd8\xff'):
        return True, "jpeg"
    elif image_data.startswith(b'\x89PNG\r\n\x1a\n'):
        return True, "png"
    elif image_data.startswith(b'GIF87a') or image_data.startswith(b'GIF89a'):
        return True, "gif"
    elif image_data.startswith(b'RIFF') and b'WEBP' in image_data[:12]:
        return True, "webp"
    
    # Fallback to filename extension if magic bytes don't match
    if filename:
        ext = filename.lower().split('.')[-1]
        if ext in ['jpg', 'jpeg', 'png', 'gif', 'webp']:
            return True, ext
    
    return False, "unknown"

