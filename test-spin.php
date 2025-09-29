<!DOCTYPE html>
<html>
<head>
    <title>Test Spin API</title>
</head>
<body>
    <h1>Test Spin API</h1>
    <button onclick="testSpin()">Test Spin</button>
    <div id="result"></div>
    
    <script>
        async function testSpin() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = 'Testing...';
            
            try {
                const response = await fetch('api/spin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        recordedId: 'TEST-' + Date.now()
                    })
                });
                
                const data = await response.json();
                
                resultDiv.innerHTML = `
                    <h3>Response:</h3>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                    <p>Status: ${response.status}</p>
                `;
                
            } catch (error) {
                resultDiv.innerHTML = `
                    <h3>Error:</h3>
                    <pre>${error.message}</pre>
                `;
            }
        }
    </script>
</body>
</html>