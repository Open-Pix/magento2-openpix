const fs = require('fs');
const vm = require('vm');
const path = require('path');

function runAmdFile(filePath, mocks) {
  const code = fs.readFileSync(filePath, 'utf8');
  let returned;
  const define = function (deps, factory) {
    const resolved = deps.map(
      (d) => mocks[d] || mocks[d.replace('./', '')] || {},
    );
    returned = factory(...resolved);
  };

  const ctx = vm.createContext({ define, console });
  const script = new vm.Script(code, { filename: filePath });
  script.runInContext(ctx);
  return returned;
}

describe('openpix_discount component', () => {
  const file = path.resolve(
    __dirname,
    '../../Pix/view/frontend/web/js/view/summary/openpix_discount.js',
  );

  it('returns 0 when no totals', () => {
    const Component = { extend: (o) => o };
    // provide empty total_segments to avoid runtime errors in getPureValue
    const quote = { getTotals: () => () => ({ total_segments: [] }) };
    const comp = runAmdFile(file, {
      jquery: { each: (arr, cb) => arr.forEach((v, i) => cb(i, v)) },
      'Magento_Checkout/js/view/summary/abstract-total': Component,
      'Magento_Checkout/js/model/quote': quote,
    });
    expect(typeof comp.getPureValue).toBe('function');
    expect(comp.getPureValue()).toBe(0);
  });

  it('reads openpix_discount direct value', () => {
    const Component = { extend: (o) => o };
    const totalsObj = { openpix_discount: '15.5' };
    const quote = { getTotals: () => () => totalsObj };
    const comp = runAmdFile(file, {
      jquery: {},
      'Magento_Checkout/js/view/summary/abstract-total': Component,
      'Magento_Checkout/js/model/quote': quote,
    });
    expect(comp.getPureValue()).toBeCloseTo(15.5);
  });

  it('reads openpix_discount from total_segments', () => {
    const Component = { extend: (o) => o };
    const totalsObj = {
      total_segments: [
        { code: 'subtotal', value: 10 },
        { code: 'openpix_discount', value: -5 },
      ],
    };
    const quote = { getTotals: () => () => totalsObj };
    const comp = runAmdFile(file, {
      jquery: { each: (arr, cb) => arr.forEach((v, i) => cb(i, v)) },
      'Magento_Checkout/js/view/summary/abstract-total': Component,
      'Magento_Checkout/js/model/quote': quote,
    });
    expect(comp.getPureValue()).toBe(-5);
  });
});
