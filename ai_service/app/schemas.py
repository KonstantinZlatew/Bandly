from pydantic import BaseModel, Field

class EvalRequest(BaseModel):
    task_type: str = Field(default="task2", pattern="^(task1|task2)$")
    task_prompt: str = Field(..., min_length=5)
    essay: str = Field(..., min_length=20)

class EvalResponse(BaseModel):
    overall_band: float
    TR: float
    CC: float
    LR: float
    GRA: float
    comment: str
