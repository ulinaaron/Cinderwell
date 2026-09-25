const openers = new WeakMap();

const openModal = (trigger) => {
  const id = trigger.getAttribute("data-cw-loop-modal-open");
  const dialog = id ? document.getElementById(id) : null;
  if (!(dialog instanceof HTMLDialogElement)) return;

  openers.set(dialog, trigger);
  if (typeof dialog.showModal === "function") {
    dialog.showModal();
    document.documentElement.classList.add("cinderwell-has-open-modal");
  }
};

const closeModal = (dialog) => {
  if (dialog instanceof HTMLDialogElement && dialog.open) dialog.close();
};

document.addEventListener("click", (event) => {
  const trigger = event.target.closest("[data-cw-loop-modal-open]");
  if (trigger) {
    event.preventDefault();
    openModal(trigger);
    return;
  }

  const close = event.target.closest("[data-cw-loop-modal-close]");
  if (close) {
    closeModal(close.closest("dialog"));
    return;
  }

  if (
    event.target instanceof HTMLDialogElement &&
    event.target.classList.contains("cinderwell-loop__modal")
  ) {
    closeModal(event.target);
  }
});

document.addEventListener(
  "close",
  (event) => {
    const dialog = event.target;
    if (!dialog.classList?.contains("cinderwell-loop__modal")) return;

    document.documentElement.classList.remove("cinderwell-has-open-modal");
    const trigger = openers.get(dialog);
    if (trigger?.isConnected) trigger.focus();
    openers.delete(dialog);
  },
  true,
);
