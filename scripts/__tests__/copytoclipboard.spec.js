const fs = require('fs');
const vm = require('vm');
const path = require('path');

describe('copytoclipboard.js', () => {
  const file = path.resolve(
    __dirname,
    '../../Pix/view/frontend/web/js/copytoclipboard.js',
  );

  function runWithFakeDOM() {
    const code = fs.readFileSync(file, 'utf8');
    const fakeElement = {
      addEventListener: jest.fn((ev, cb) => {
        // store callback for manual trigger
        fakeElement._cb = cb;
      }),
      select: jest.fn(),
    };

    const fakeDocument = {
      getElementById: jest.fn((id) => {
        if (id === 'clickMe') return fakeElement;
        if (id === 'select-this') return { select: jest.fn() };
        return null;
      }),
    };

    const sandbox = {
      window: {},
      document: fakeDocument,
      documentexecCommandCalled: false,
      documentexecCommand: jest.fn(),
    };

    // polyfill document.execCommand for the inline call
    sandbox.document.execCommand = jest.fn();

    const ctx = vm.createContext(Object.assign({ console }, sandbox));
    const script = new vm.Script(code, { filename: file });
    script.runInContext(ctx);

    // trigger onload to simulate browser behavior
    if (typeof ctx.window.onload === 'function') {
      ctx.window.onload();
    }

    return { fakeElement, fakeDocument, ctx };
  }

  it('attaches click listener when button exists', () => {
    const { fakeElement } = runWithFakeDOM();
    expect(fakeElement.addEventListener).toHaveBeenCalled();
  });
});
