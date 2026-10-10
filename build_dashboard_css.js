const { execSync } = require('child_process');
const fs = require('fs');

const tailwindConfig = `
module.exports = {
  content: [
    './resources/views/**/*.blade.php',
    './modelo tela dash/code.html',
  ],
  important: '.financeiro-dashboard-container',
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      },
      boxShadow: {
        '2xs': '0 1px 2px 0 rgba(0, 0, 0, 0.03)',
        'xs': '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
      },
      colors: {
        brand: {
          petroleum: '#47728f',
          'petroleum-dark': '#355972',
          'petroleum-light': '#5d85a0',
          ieducar: '#47728f',
          'ieducar-dark': '#355972',
          green: '#10b981',
          'green-dark': '#059669',
          coral: '#ef4444',
          amber: '#f59e0b',
          surface: '#f8fafc',
        }
      }
    }
  },
  corePlugins: {
    preflight: false,
  }
};
`;

fs.writeFileSync('temp_tailwind.config.js', tailwindConfig);
fs.writeFileSync('temp_input.css', '@tailwind base;\n@tailwind components;\n@tailwind utilities;\n');

console.log('Compiling Tailwind CSS with CLI...');
execSync('npx -y tailwindcss@3 -c temp_tailwind.config.js -i temp_input.css -o temp_output.css --minify');

let css = fs.readFileSync('temp_output.css', 'utf8');

// Scope the base CSS variables strictly to .financeiro-dashboard-container
css = css.replace(/\*,:after,:before\{/, '.financeiro-dashboard-container *,.financeiro-dashboard-container :after,.financeiro-dashboard-container :before{');

// Add additional custom styles to match screen.png and fill 100% available area
const extraStyles = `
/* Inter Font */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

/* Remove espaçamento excessivo do layout i-Educar ao redor do painel */
#corpo {
    padding: 0 !important;
}
#corpo > table.tablelistagem {
    display: none !important;
}
#financeiro-module {
    padding: 0 !important;
}

/* Dashboard Container Scoping */
.financeiro-dashboard-container {
    font-family: 'Inter', sans-serif !important;
    background-color: #f4f7f9 !important;
    width: 100% !important;
}

.financeiro-dashboard-container * {
    box-sizing: border-box;
}

/* Tables in Dashboard */
.financeiro-dashboard-container table {
    border-collapse: collapse !important;
    width: 100% !important;
    margin: 0 !important;
}

.financeiro-dashboard-container th, 
.financeiro-dashboard-container td {
    border: none !important;
}

/* Status Pill Badges */
.financeiro-dashboard-container .rounded-full {
    border-radius: 9999px !important;
}

/* SVG Icon Safety */
.financeiro-dashboard-container svg {
    display: inline-block !important;
    vertical-align: middle !important;
    flex-shrink: 0 !important;
}

/* Paginação Limpa e Moderna */
.financeiro-dashboard-container .pagination {
    display: inline-flex !important;
    gap: 4px !important;
    list-style: none !important;
    margin: 0 !important;
    padding: 0 !important;
    align-items: center !important;
}
.financeiro-dashboard-container .page-item .page-link,
.financeiro-dashboard-container .pagination a,
.financeiro-dashboard-container .pagination span {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    min-width: 32px !important;
    height: 32px !important;
    padding: 0 8px !important;
    font-size: 12px !important;
    font-weight: 500 !important;
    border-radius: 4px !important;
    border: 1px solid #cbd5e1 !important;
    background-color: #ffffff !important;
    color: #334155 !important;
    text-decoration: none !important;
    transition: all 0.15s ease !important;
}
.financeiro-dashboard-container .pagination .active span,
.financeiro-dashboard-container .pagination .active .page-link,
.financeiro-dashboard-container .pagination span[aria-current="page"] {
    background-color: #47728f !important;
    color: #ffffff !important;
    border-color: #47728f !important;
}
.financeiro-dashboard-container .pagination a:hover {
    background-color: #f1f5f9 !important;
    border-color: #94a3b8 !important;
}
.financeiro-dashboard-container .pagination .disabled span,
.financeiro-dashboard-container .pagination .disabled .page-link {
    opacity: 0.5 !important;
    cursor: not-allowed !important;
}
`;

const finalCss = extraStyles.trim() + '\n' + css;
fs.writeFileSync('resources/views/dashboard/compiled_dashboard.css', finalCss);
fs.writeFileSync('resources/views/dashboard/styles.blade.php', '<style>\n' + finalCss + '\n</style>\n');

// Clean temp files
try {
  fs.unlinkSync('temp_tailwind.config.js');
  fs.unlinkSync('temp_input.css');
  fs.unlinkSync('temp_output.css');
} catch (e) {}

console.log('Final compiled CSS written to compiled_dashboard.css and styles.blade.php');
console.log('Total CSS size:', finalCss.length, 'bytes');
