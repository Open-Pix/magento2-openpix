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

describe('openpix-pix-method', () => {
  const file = path.resolve(
    __dirname,
    '../../Pix/view/frontend/web/js/view/payment/method-renderer/openpix-pix-method.js',
  );

  it('provides getCode and getData', () => {
    const Component = { extend: (o) => o };
    const module = runAmdFile(file, {
      'Magento_Checkout/js/view/payment/default': Component,
      jquery: {},
      'Magento_Checkout/js/model/payment/additional-validators': {},
      'Magento_Ui/js/model/messageList': {},
    });
    expect(typeof module.getCode).toBe('function');
    expect(typeof module.getData).toBe('function');
    const data = module.getData.call({ item: { method: 'openpix_pix' } });
    expect(data).toEqual({ method: 'openpix_pix' });
    expect(module.getCode()).toBe('openpix_pix');
  });
});
