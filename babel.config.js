module.exports = {
    presets: [
        ['@babel/preset-env', {targets: {node: 'current'}}],
        '@babel/react',
        ['@babel/preset-typescript', { allowDeclareFields: true }]
    ],
};
