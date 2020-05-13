const nearApi  = require('near-api-js')

const keypair = nearApi.utils.KeyPair.fromRandom('ed25519');

console.log(JSON.stringify({public: keypair.publicKey.toString(), private: keypair.secretKey}));