document.addEventListener("DOMContentLoaded", function () {
  if (
    window.wc?.wcBlocksRegistry?.registerPaymentMethod &&
    window.wp?.element &&
    window.wc?.wcSettings
  ) {
    const settings = window.wc.wcSettings["wipay_by_hexakode_data"] || {};
    const { createElement } = window.wp.element;

    window.wc.wcBlocksRegistry.registerPaymentMethod({
      name: "wipay_by_hexakode",
      label: createElement("span", null, settings.title || "WiPay"),
      ariaLabel: settings.ariaLabel || "WiPay",
      supports: {
        features: ["products", "subscriptions", "default", "virtual"],
      },
      canMakePayment: () => Promise.resolve(true),
      content: createElement(
        "p",
        null,
        settings.description || "Pay with WiPay"
      ),
      edit: createElement("p", null, settings.description || "Pay with WiPay"),
      save: null,
    });

    console.log("[WiPay] registered in block checkout");
  }
});
