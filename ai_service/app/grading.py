def round_to_half(x: float) -> float:
    return round(x * 2) / 2

def is_half_step(x: float) -> bool:
    return abs((x * 2) - round(x * 2)) < 1e-6

def compute_overall(tr: float, cc: float, lr: float, gra: float) -> float:
    return round_to_half((tr + cc + lr + gra) / 4.0)

def apply_length_penalty(tr: float, essay_text: str, min_words: int) -> float:
    words = len(essay_text.split())
    if words < int(min_words * 0.8):
        return min(tr, 5.0)
    if words < min_words:
        return min(tr, 5.5)
    return tr