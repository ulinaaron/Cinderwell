(function () {
  const menus = document.querySelectorAll(".cinderwell-mega-menu");
  if (!menus.length) {
    return;
  }

  const close = (menu) => {
    const toggle = menu.querySelector(":scope > .cinderwell-mega-menu__toggle");
    menu.classList.remove("is-open");
    toggle?.setAttribute("aria-expanded", "false");
  };

  const open = (menu) => {
    menus.forEach((item) => {
      if (item !== menu) {
        close(item);
      }
    });
    const toggle = menu.querySelector(":scope > .cinderwell-mega-menu__toggle");
    menu.classList.add("is-open");
    toggle?.setAttribute("aria-expanded", "true");
  };

  menus.forEach((menu) => {
    const toggle = menu.querySelector(":scope > .cinderwell-mega-menu__toggle");
    if (!toggle) {
      return;
    }

    menu.classList.add("is-enhanced");

    toggle.addEventListener("click", () => {
      if (menu.classList.contains("is-open")) {
        close(menu);
      } else {
        open(menu);
      }
    });

    menu.addEventListener("focusout", (event) => {
      if (!menu.contains(event.relatedTarget)) {
        close(menu);
      }
    });

    menu.addEventListener("keydown", (event) => {
      if (event.key === "Escape") {
        toggle.focus();
        close(menu);
      }
    });
  });

  document.addEventListener("pointerdown", (event) => {
    menus.forEach((menu) => {
      if (!menu.contains(event.target)) {
        close(menu);
      }
    });
  });
})();
