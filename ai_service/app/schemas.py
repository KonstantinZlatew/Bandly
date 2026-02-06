from pydantic import BaseModel, Field

class EvalRequest(BaseModel):
    task_type: str = Field(..., pattern="^(academic_task_1|general_task_1|task_2)$")
    task_prompt: str = Field(..., min_length=5)
    essay: str = Field(..., min_length=20)

class EvalResponse(BaseModel):
    overall_band: float
    TR: float
    CC: float
    LR: float
    GRA: float
    comment: str
