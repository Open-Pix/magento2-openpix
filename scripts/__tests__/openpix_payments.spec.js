const fs = require('fs');
const vm = require('vm');
const path = require('path');

function runAmdFile(filePath, mocks) {
  const code = fs.readFileSync(filePath, 'utf8');
  let returned;
  const define = function (deps, factory) {
    const resolved = deps.map((d) => mocks[d] || {});
    returned = factory(...resolved);
  };

  const ctx = vm.createContext({ define, console });
  const script = new vm.Script(code, { filename: filePath });
  script.runInContext(ctx);
  return returned;
}

describe('openpix_payments', () => {
  const file = path.resolve(
    __dirname,
    '../../Pix/view/frontend/web/js/view/payment/openpix_payments.js',
  );

  it('pushes a renderer to rendererList', () => {
    const rendererList = [];
    const Component = { extend: () => ({}) };
    runAmdFile(file, {
      uiComponent: Component,
      'Magento_Checkout/js/model/payment/renderer-list': rendererList,
    });
    expect(rendererList.length).toBeGreaterThan(0);
    const found = rendererList.find((r) => r.type === 'openpix_pix');
    expect(found).toBeDefined();
    expect(found.component).toMatch(/openpix_pix_method|openpix-pix-method/);
  });
});
