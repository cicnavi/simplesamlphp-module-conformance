
(function() {
    window.conformance = {};
    window.conformance.nuclei = {};

    const serviceProviderEntityIdSelect = document.getElementById('serviceProviderEntityId');

    /**
     * Populate available ACSs for specific SP Entity ID.
     */
    window.conformance.nuclei.fetchAcss = function () {

        // Clear all existing options
        const assertionConsumerServiceIdSelect = document.getElementById('assertionConsumerServiceUrl');
        assertionConsumerServiceIdSelect.disabled = true;
        assertionConsumerServiceIdSelect.innerHTML = '';

        // Create a new default option
        const defaultOption = document.createElement('option');
        defaultOption.text = '';
        defaultOption.value = '';
        defaultOption.selected = true; // This makes it the default selected option
        assertionConsumerServiceIdSelect.add(defaultOption);

        // Define the query parameter
        const spEntityId = serviceProviderEntityIdSelect.value;

        if (! spEntityId) {
            return;
        }

        // Construct the URL with the query parameter
        const urlWithQueryParam = `fetch-acss?spEntityId=${encodeURIComponent(spEntityId)}`;

        // Make the GET request using fetch
        fetch(urlWithQueryParam)
            .then(response => {
                // Check if the request was successful
                if (!response.ok) {
                    throw new Error('Error fetching ACSs. Network response was not ok.');
                }
                // Parse the JSON response
                return response.json();
            })
            .then(data => {
                // Handle the data returned from the server
                // Add new options
                data.forEach(function(optionText, index) {
                    const option = document.createElement('option');
                    option.text = optionText;
                    option.value = optionText; // You can set value according to your need
                    assertionConsumerServiceIdSelect.add(option);
                });
            })
            .catch(error => {
                // Handle any errors that occurred during the fetch
                console.error('Error fetching ACSs:', error);
            })
            .finally(() => {
                assertionConsumerServiceIdSelect.disabled = false;
            });
    };

    const testRunButton = document.getElementById('nuclei-run-test-btn');

    /**
     * Call an endpoint to run specific test using Nuclei.
     */
    window.conformance.nuclei.runTest = function () {
        testRunButton.disabled = true;

        const outputElement = document.getElementById('cmd-output');
        outputElement.innerHTML = 'Waiting...';

        const testTypeInputElement = document.getElementById('testTypeId');
        const enableDebugElement = document.getElementById('enableDebug');
        const enableVerboseElement = document.getElementById('enableVerbose');
        const enableFindingsExportElement = document.getElementById('enableFindingsExport');
        const enableJsonExportElement = document.getElementById('enableJsonExport');
        const enableJsonLExportElement = document.getElementById('enableJsonLExport');
        const enableSarifExportElement = document.getElementById('enableSarifExport');
        const enableMarkdownExportElement = document.getElementById('enableMarkdownExport');

        const formData = new URLSearchParams();
        formData.append('testTypeId', testTypeInputElement.value);
        formData.append('serviceProviderEntityId', serviceProviderEntityIdSelect.value);
        formData.append('enableDebug', enableDebugElement.checked ? '1' : '0');
        formData.append('enableVerbose', enableVerboseElement.checked ? '1' : '0');
        formData.append('enableFindingsExport', enableFindingsExportElement.checked ? '1' : '0');
        formData.append('enableJsonExport', enableJsonExportElement.checked ? '1' : '0');
        formData.append('enableJsonLExport', enableJsonLExportElement.checked ? '1' : '0');
        formData.append('enableSarifExport', enableSarifExportElement.checked ? '1' : '0');
        formData.append('enableMarkdownExport', enableMarkdownExportElement.checked ? '1' : '0');

        fetch('run', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData.toString()
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
                                outputElement.innerHTML += new TextDecoder().decode(value);
                            }
                        } finally {
                            reader.releaseLock();
                            testRunButton.disabled = false;
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
            })
            .finally(() => {
                testRunButton.disabled = false;
            });
    };

    // Add common event listeners.
    testRunButton.addEventListener('click', window.conformance.nuclei.runTest);
    serviceProviderEntityIdSelect.addEventListener('change', window.conformance.nuclei.fetchAcss);

    // Fetch ACS for currently selected SP right away.
    window.conformance.nuclei.fetchAcss();
})();


