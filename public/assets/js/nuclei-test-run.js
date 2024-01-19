
(function() {
    const outputElement = document.getElementById('output');
    outputElement.textContent = 'Waiting...';

    const data = {
        key1: 'value1',
        key2: 'value2'
    };

    fetch('run', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok.');
            }

            const reader = response.body.getReader();

            let outed = false;
            return new ReadableStream({
                async start(controller) {
                    try {
                        while (true) {
                            const { done, value } = await reader.read();

                            if (done) {
                                break;
                            }

                            if (!outed) {
                                outputElement.innerText = '';
                                outed = true;
                            }

                            // Process the chunk (value) and update the UI
                            outputElement.innerText += new TextDecoder().decode(value);
                        }
                    } finally {
                        reader.releaseLock();
                    }
                },
            });
        })
        .then(stream => new Response(stream))
        .then(response => response.text())
        .then(data => {
            // You can perform additional processing on the complete response if needed
        })
        .catch(error => {
            console.error('Error:', error);
            outputElement.textContent = error;
        });
})();
