// Simple hover effect for buttons
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px)';
                this.style.boxShadow = '0 7px 15px rgba(0, 0, 0, 0.2)';
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 4px 10px rgba(0, 0, 0, 0.1)';
            });
        });
        
        // Feature card hover effect
        document.querySelectorAll('.feature-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
        
        // Preview button functionality
        document.querySelector('.preview-button').addEventListener('click', function() {
            alert('Live preview functionality would be implemented here!');
        });

  const preview = document.querySelector('.preview-3d');
  let angle = 0;
  setInterval(() => {
    angle += 0.5;
    preview.style.transform = `rotateY(${angle}deg)`;
  }, 100);

