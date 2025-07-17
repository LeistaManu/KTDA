document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('termsModal');
    const openModalBtn = document.getElementById('openTermsModal');
    const closeModalBtn = document.querySelector('.close-button');
    
    // Toggle modal visibility
    openModalBtn.addEventListener('click', function(e) {
      e.preventDefault();
      modal.style.display = 'flex';
    });
    
    closeModalBtn.addEventListener('click', function() {
      modal.style.display = 'none';
    });
    
    // Close modal when clicking outside content
    window.addEventListener('click', function(e) {
      if (e.target === modal) {
        modal.style.display = 'none';
      }
    });
    
    // Show/hide other period input
    const periodSelect = document.getElementById('attachmentPeriod');
    const otherPeriodDiv = document.getElementById('otherPeriodDiv');
    
    periodSelect.addEventListener('change', function() {
      otherPeriodDiv.style.display = 
        (this.value === 'other') ? 'block' : 'none';
    });
  });