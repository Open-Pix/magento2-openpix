const fs = require('fs');
const vm = require('vm');
const path = require('path');

function runFile(filePath, context = {}) {
  const code = fs.readFileSync(filePath, 'utf8');
  const script = new vm.Script(code, { filename: filePath });
  const ctx = vm.createContext(Object.assign({ console }, context));
  script.runInContext(ctx);
  return ctx;
}

describe('mascara.js', () => {
  const file = path.resolve(
    __dirname,
    '../../Pix/view/frontend/web/js/mascara.js',
  );

  it('should format CPF correctly', () => {
    const ctx = runFile(file);
    const cpf = '12345678901';
    const formatted = ctx.cpfCnpj(cpf);
    expect(formatted).toBe('123.456.789-01');
  });

  it('should format CNPJ correctly', () => {
    const ctx = runFile(file);
    const cnpj = '12345678000199';
    const formatted = ctx.cpfCnpj(cnpj);
    // expect CNPJ style: 12.345.678/0001-99
    expect(formatted).toBe('12.345.678/0001-99');
  });
});
