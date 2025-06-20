(function () {
  const interval = setInterval(() => {
    if (
      window.wc?.wcBlocksRegistry &&
      window.wc?.wcSettings &&
      window.wp?.element
    ) {
      clearInterval(interval);

      const settings = window.wc.wcSettings["wipay_by_hexakode_data"] || {};
      const { createElement } = window.wp.element;

      console.log("[WiPay] Block script loaded", settings);

      window.wc.wcBlocksRegistry.registerPaymentMethod({
        name: "wipay_by_hexakode",
        label: createElement("span", null, settings.title || "WiPay"),
        ariaLabel: settings.ariaLabel || "WiPay",
        supports: ["products", "subscriptions", "default", "virtual"],
        canMakePayment: () => Promise.resolve(true),
        content: createElement(
          "p",
          null,
          settings.description || "Pay with WiPay"
        ),
        edit: createElement(
          "p",
          null,
          settings.description || "Pay with WiPay"
        ),
        save: null,
      });
    }
  }, 100);
})();
