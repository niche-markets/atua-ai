  // webpack.config.cjs

  const path = require('path');
  const { CleanWebpackPlugin } = require('clean-webpack-plugin');
  const webpack = require('webpack');
  require('dotenv').config();

  module.exports = {
    mode: 'production',
    entry: './resources/assets/js/web3/index.js',
    output: {
      filename: 'main.js',
      path: path.resolve(__dirname, 'public/assets/js/web3'),
      publicPath: '/assets/js/web3/',
      chunkFilename: '[id].[contenthash].js',
    },
    module: {
      rules: [
        {
          test: /\.(js|jsx)$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: ['@babel/preset-env', '@babel/preset-react'],
            },
          },
        },
      ],
    },
    plugins: [
      new CleanWebpackPlugin(),
      // Inject environment variables
      new webpack.DefinePlugin({
        'process.env': JSON.stringify({
          REACT_APP_PROJECT_ID: process.env.REACT_APP_PROJECT_ID,
          REACT_APP_PROJECT_URL: process.env.REACT_APP_PROJECT_URL,
          REACT_APP_AGII_BSC_TOKEN_ADDRESS: process.env.REACT_APP_AGII_BSC_TOKEN_ADDRESS,
          REACT_APP_BSC_TARGET_ADDRESS: process.env.REACT_APP_BSC_TARGET_ADDRESS,
          REACT_APP_CMC_API_KEY: process.env.REACT_APP_CMC_API_KEY,
          REACT_APP_TEST_TOKEN_ADDRESS: process.env.REACT_APP_TEST_TOKEN_ADDRESS,
          REACT_APP_CG_API_KEY: process.env.REACT_APP_CG_API_KEY
        }),
      }),
    ],
    resolve: {
      extensions: ['.js', '.jsx'],
    },
  };
