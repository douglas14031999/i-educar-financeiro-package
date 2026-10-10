const { execSync } = require('child_process');
const fs = require('fs');

const tailwindConfig = `
module.exports = {
  content: [
    './resources/views/dashboard/index.blade.php',
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
          petroleum: '#1d4354',
          'petroleum-dark': '#132d39',
          'petroleum-light': '#2d637a',
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
