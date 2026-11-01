const TIMER_INTERVAL_MS = 100;

const answerForm = document.querySelector('.answer-form');
const submitButton = answerForm.querySelector('button[type="submit"]');
const timer = document.querySelector('.timer');
const startedAt = Date.now() - Number(timer.dataset.elapsed) * 1000;

function formatTime(seconds) {
  const minutes = Math.floor(seconds / 60);
  const remainingSeconds = (seconds - minutes * 60).toFixed(1).replace('.', ',');

  if (minutes === 0) {
    return `${remainingSeconds}s`;
  }

  return `${minutes}min ${remainingSeconds}s`;
}

function updateTimer() {
  timer.textContent = formatTime((Date.now() - startedAt) / 1000);
}

function hasSelectedOption() {
  return answerForm.querySelector('input[name="option"]:checked') !== null;
}

function updateSubmitButton() {
  submitButton.disabled = !hasSelectedOption();
}

updateSubmitButton();
setInterval(updateTimer, TIMER_INTERVAL_MS);
answerForm.addEventListener('change', updateSubmitButton);
answerForm.addEventListener('submit', () => {
  submitButton.disabled = true;
});
