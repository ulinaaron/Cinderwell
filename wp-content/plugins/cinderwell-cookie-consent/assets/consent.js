(() => {
  "use strict";

  const settings = window.cinderwellConsentSettings || {};
  const root = document.querySelector("[data-cw-consent-root]");
  const dialog = document.querySelector("[data-cw-consent-dialog]");
  const manage = document.querySelector(".cw-consent-manage");
  const optional = ["preferences", "analytics", "marketing"];
  let existing = null;

  const read = () => {
    const prefix = `${settings.cookieName}=`;
    const entry = document.cookie
      .split(";")
      .map((part) => part.trim())
      .find((part) => part.startsWith(prefix));
    if (!entry) return null;
    try {
      const value = JSON.parse(decodeURIComponent(entry.slice(prefix.length)));
      return value.version === settings.version && value.categories
        ? value
        : null;
    } catch (error) {
      return null;
    }
  };

  const write = (categories) => {
    const value = encodeURIComponent(
      JSON.stringify({
        version: settings.version,
        timestamp: new Date().toISOString(),
        categories,
      }),
    );
    document.cookie = `${settings.cookieName}=${value}; path=/; max-age=${
      Number(settings.expiryDays || 180) * 86400
    }; SameSite=Lax${settings.secure ? "; Secure" : ""}`;
  };

  const has = (category) =>
    category === "necessary" || Boolean(read()?.categories?.[category]);

  const activate = () => {
    document
      .querySelectorAll("script[data-cw-consent-script]")
      .forEach((blocked) => {
        if (
          !has(blocked.dataset.cwConsentCategory) ||
          blocked.dataset.cwActivated
        )
          return;
        const script = document.createElement("script");
        [...blocked.attributes].forEach(({ name, value }) => {
          if (!name.startsWith("data-cw-consent") && name !== "type")
            script.setAttribute(name, value);
        });
        if (!script.hasAttribute("async")) script.async = false;
        script.textContent = blocked.textContent;
        blocked.replaceWith(script);
      });
    document
      .querySelectorAll("link[data-cw-consent-style]")
      .forEach((blocked) => {
        if (
          !has(blocked.dataset.cwConsentCategory) ||
          blocked.dataset.cwActivated
        )
          return;
        const link = document.createElement("link");
        link.rel = "stylesheet";
        link.href = blocked.dataset.cwHref;
        link.media = blocked.dataset.cwMedia || "all";
        blocked.replaceWith(link);
      });
    document
      .querySelectorAll("iframe[data-cw-consent-frame]")
      .forEach((frame) => {
        if (has(frame.dataset.cwConsentCategory) && !frame.src)
          frame.src = frame.dataset.cwSrc;
      });
    window.dispatchEvent(
      new CustomEvent("cinderwell:consent", { detail: read() }),
    );
  };

  const expireCookie = (name) => {
    const host = window.location.hostname;
    const domains = [
      "",
      host,
      `.${host}`,
      `.${host.split(".").slice(-2).join(".")}`,
    ];
    domains.forEach((domain) => {
      document.cookie = `${name}=; path=/; max-age=0; SameSite=Lax${
        domain ? `; domain=${domain}` : ""
      }`;
    });
  };

  const clearRejected = (categories) => {
    optional.forEach((category) => {
      if (categories[category]) return;
      (settings.cookieNames?.[category] || []).forEach((pattern) => {
        const prefix = pattern.endsWith("*") ? pattern.slice(0, -1) : null;
        document.cookie.split(";").forEach((part) => {
          const name = part.split("=")[0].trim();
          if (name === pattern || (prefix && name.startsWith(prefix)))
            expireCookie(name);
        });
      });
    });
  };

  const choices = (allow) =>
    Object.fromEntries(optional.map((key) => [key, allow]));
  const syncForm = (categories = choices(false)) => {
    optional.forEach((key) => {
      const input = dialog?.querySelector(`[name="${key}"]`);
      if (input) input.checked = Boolean(categories[key]);
    });
  };
  const close = () => {
    if (dialog?.open) dialog.close();
  };
  const commit = (categories) => {
    const hadChoice = Boolean(read());
    write(categories);
    existing = read();
    clearRejected(categories);
    root.hidden = true;
    manage.hidden = false;
    close();
    if (hadChoice) window.location.reload();
    else activate();
  };
  const open = () => {
    syncForm(read()?.categories);
    if (typeof dialog?.showModal === "function") dialog.showModal();
    else dialog?.setAttribute("open", "");
  };

  document.addEventListener("click", (event) => {
    const button = event.target.closest("[data-cw-consent-action]");
    if (!button) return;
    const action = button.dataset.cwConsentAction;
    if (action === "customize") open();
    if (action === "close") close();
    if (action === "accept") commit(choices(true));
    if (action === "reject") commit(choices(false));
    if (action === "save") {
      event.preventDefault();
      commit(
        Object.fromEntries(
          optional.map((key) => [
            key,
            Boolean(dialog.querySelector(`[name="${key}"]`)?.checked),
          ]),
        ),
      );
    }
  });

  existing = read();
  window.CinderwellConsent = { has, open, get: read };
  if (existing) {
    manage.hidden = false;
    activate();
  } else {
    root.hidden = false;
    manage.hidden = true;
    syncForm(choices(false));
  }
})();
