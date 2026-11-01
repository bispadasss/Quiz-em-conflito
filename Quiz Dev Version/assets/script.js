const FLASH_DURATION_MS = 4000;

const flashMessage = document.querySelector('.flash');

if (flashMessage) {
  setTimeout(() => flashMessage.classList.add('is-hidden'), FLASH_DURATION_MS);
}
