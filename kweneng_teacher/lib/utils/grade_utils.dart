String? gradeForScore(double? score) {
  if (score == null) return null;
  if (score > 89) return 'A*';
  if (score > 79) return 'A';
  if (score > 69) return 'B';
  if (score > 59) return 'C';
  if (score > 49) return 'D';
  if (score > 39) return 'E';
  if (score > 34) return 'F';
  return 'G';
}

String? gradeForScores(double? midtermScore, double? endtermScore) {
  if (midtermScore != null && endtermScore != null) {
    return gradeForScore((midtermScore + endtermScore) / 2);
  }

  return gradeForScore(midtermScore ?? endtermScore);
}
