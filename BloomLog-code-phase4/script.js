// Shared utility functions
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
}
document.addEventListener("keydown", e => {
    if (e.key === "Enter" || e.key === " ") {
        if (document.activeElement && document.activeElement.click) {
            document.activeElement.click();
        }
    }
});

document.querySelectorAll(".card, .btn, .plant-actions a, .plant-detail-image, .my-plant-card").forEach(el => {
    if (!el.hasAttribute("tabindex")) {
        el.setAttribute("tabindex", "0"); 
        el.setAttribute("role", "button");
    }
});
// Common form validation
document.addEventListener('DOMContentLoaded', function() {
    // Add basic form validation to all forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.closest('.form-group').classList.add('error');
                } else {
                    field.closest('.form-group').classList.remove('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });

});
