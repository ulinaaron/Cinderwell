(function () {
  const searches = document.querySelectorAll(
    ".cinderwell-header-search:not(.is-editor-preview)",
  );
  if (!searches.length) return;

  const close = (search, restoreFocus = false) => {
    const toggle = search.querySelector(".cinderwell-header-search__toggle");
    const panel = search.querySelector(".cinderwell-header-search__panel");
    search.classList.remove("is-open");
    toggle?.setAttribute("aria-expanded", "false");
    if (panel) panel.hidden = true;
    if (restoreFocus) toggle?.focus();
  };

  const closeOtherHeaderDisclosures = () => {
    document
      .querySelectorAll(".cinderwell-mega-menu.is-open")
      .forEach((menu) => {
        menu.classList.remove("is-open");
        menu
          .querySelector(":scope > .cinderwell-mega-menu__toggle")
          ?.setAttribute("aria-expanded", "false");
      });
  };

  const open = (search) => {
    searches.forEach((item) => {
      if (item !== search) close(item);
    });
    closeOtherHeaderDisclosures();
    const toggle = search.querySelector(".cinderwell-header-search__toggle");
    const panel = search.querySelector(".cinderwell-header-search__panel");
    search.classList.add("is-open");
    toggle?.setAttribute("aria-expanded", "true");
    if (panel) panel.hidden = false;
    window.requestAnimationFrame(() =>
      panel?.querySelector('input[type="search"]')?.focus(),
    );
  };

  searches.forEach((search) => {
    const toggle = search.querySelector(".cinderwell-header-search__toggle");
    if (!toggle) return;

    search.classList.add("is-enhanced");
    toggle.addEventListener("click", () => {
      if (search.classList.contains("is-open")) close(search, true);
      else open(search);
    });

    search.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && search.classList.contains("is-open")) {
        event.preventDefault();
        close(search, true);
      }
    });

    search.addEventListener("focusout", (event) => {
      if (!search.contains(event.relatedTarget)) close(search);
    });
  });

  document.addEventListener("pointerdown", (event) => {
    searches.forEach((search) => {
      if (!search.contains(event.target)) close(search);
    });
  });

  document
    .querySelectorAll(".cinderwell-mega-menu__toggle")
    .forEach((toggle) => {
      toggle.addEventListener("click", () =>
        searches.forEach((search) => close(search)),
      );
    });
})();
