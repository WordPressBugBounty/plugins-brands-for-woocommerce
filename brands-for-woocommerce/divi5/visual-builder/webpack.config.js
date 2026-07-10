const path = require('path');

module.exports = {
  entry: {
    bundle: './src/index.jsx',
  },
  externals: {
    react: ['vendor', 'React'],
    '@wordpress/hooks': ['vendor', 'wp', 'hooks'],
    '@divi/module-library': ['divi', 'moduleLibrary'],
  },
  module: {
    rules: [
      {
        test: /\.jsx?$/,
        exclude: /node_modules/,
        use: [
          {
            loader: 'babel-loader',
            options: {
              compact: false,
              presets: [
                ['@babel/preset-env', {
                  modules: false,
                  targets: '> 5%',
                }],
                '@babel/preset-react',
              ],
              cacheDirectory: false,
            },
          },
        ],
      },
    ],
  },
  resolve: {
    extensions: ['.js', '.jsx', '.json'],
  },
  output: {
    filename: 'brands-for-woocommerce-divi5.js',
    path: path.resolve(__dirname, 'build'),
  },
};
