def round_to_half(x: float) -> float:
    return round(x * 2) / 2

def compute_task2_overall(tr: float, cc: float, lr: float, gra: float) -> float:
    return round_to_half((tr + cc + lr + gra) / 4.0)

def apply_task2_length_penalty(tr: float, essay_text: str) -> float:
    words = len(essay_text.split())
    if words < 200:
        return min(tr, 5.0)
    if words < 250:
        return min(tr, 5.5)
    return tr

def is_half_step(x: float) -> bool:
    return abs((x * 2) - round(x * 2)) < 1e-6
