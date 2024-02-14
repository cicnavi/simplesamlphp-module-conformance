
(function() {
    const resultsFormElement = document.getElementById('resultsForm');
    const serviceProviderEntityIdSelect = document.getElementById('serviceProviderEntityId');

    serviceProviderEntityIdSelect.addEventListener('change', function () {
        if (serviceProviderEntityIdSelect.value) {
            resultsFormElement.submit();
        }
    });
})();


