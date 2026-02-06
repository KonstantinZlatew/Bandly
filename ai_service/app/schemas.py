from pydantic import BaseModel, Field
from typing import Optional

class EvalRequest(BaseModel):
    task_type: str = Field(..., pattern="^(academic_task_1|general_task_1|task_2)$")
    task_prompt: str = Field(..., min_length=5)
    essay: str = Field(..., min_length=20)
    # Image is optional and only used for academic_task_1
    image_url: Optional[str] = Field(None, description="URL or base64 encoded image for academic_task_1")
    image_base64: Optional[str] = Field(None, description="Base64 encoded image data")

class EvalResponse(BaseModel):
    overall_band: float
    TR: float
    CC: float
    LR: float
    GRA: float
    comment: str
