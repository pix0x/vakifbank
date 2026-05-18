const { put, get, head, list } = require('@vercel/blob');

const token = process.env.BLOB_READ_WRITE_TOKEN;

async function main() {
  console.log('Token:', token ? token.substring(0, 30) + '...' : 'MISSING');
  
  // 1. Try SDK put
  try {
    const result = await put('test.txt', 'hello world', {
      access: 'public',
      token,
      addRandomSuffix: false,
    });
    console.log('SDK PUT OK:', JSON.stringify(result, null, 2));
  } catch (e) {
    console.log('SDK PUT ERROR:', e.message);
    if (e.response) console.log('Response body:', await e.response.text());
  }

  // 2. Try list
  try {
    const all = await list({ token });
    console.log('SDK LIST OK:', JSON.stringify(all, null, 2));
  } catch (e) {
    console.log('SDK LIST ERROR:', e.message);
  }
}

main();
