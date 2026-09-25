const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');
const CopyPlugin = require('copy-webpack-plugin');
const fs = require('fs');

// Collect block entries from src/blocks/*/block.json
const blocksDir = path.resolve(__dirname, 'src/blocks');
const blockEntries = {};
const blockCopyPatterns = [];
if (fs.existsSync(blocksDir)) {
    fs.readdirSync(blocksDir).forEach((name) => {
        const blockJson = path.join(blocksDir, name, 'block.json');
        if (fs.existsSync(blockJson)) {
            blockEntries[`blocks/${name}/index`] = path.join(blocksDir, name, 'index.js');
            // Copy all non-JS files (block.json, render.php, *.css) to build
            blockCopyPatterns.push({
                from: blocksDir + '/' + name,
                to: path.resolve(__dirname, 'build/blocks', name),
                filter: (resourcePath) => {
                    return !resourcePath.endsWith('index.js');
                },
            });
        }
    });
}

module.exports = {
    ...defaultConfig,
    entry: {
        ...blockEntries,
        frontend: path.resolve(__dirname, 'src/shared/auth-state.js'),
        admin: path.resolve(__dirname, 'src/admin/settings-tabs.js'),
        'admin-email': path.resolve(__dirname, 'src/admin/email-template-editor.js'),
        'admin-style': path.resolve(__dirname, 'src/admin/admin.css'),
        'frontend-style': path.resolve(__dirname, 'src/blocks/login/style.css'),
    },
    output: {
        ...defaultConfig.output,
        path: path.resolve(__dirname, 'build'),
        filename: '[name].js',
    },
    plugins: [
        ...defaultConfig.plugins,
        new CopyPlugin({
            patterns: blockCopyPatterns,
        }),
    ],
};
