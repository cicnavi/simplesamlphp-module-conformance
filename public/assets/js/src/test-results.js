
(function() {
    const resultsFormElement = document.getElementById('resultsForm');
    const spEntityIdSelect = document.getElementById('spEntityId');

    spEntityIdSelect.addEventListener('change', function () {
        resultsFormElement.submit();
    });
})();


