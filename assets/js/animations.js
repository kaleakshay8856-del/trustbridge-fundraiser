// GSAP Animations for TrustBridge

// Initialize animations on page load
document.addEventListener('DOMContentLoaded', () => {
    initScrollAnimations();
    initHeroAnimations();
    initCardAnimations();
});

// Hero section animations
function initHeroAnimations() {
    gsap.from('.hero-title', {
        duration: 1,
        y: 50,
        opacity: 0,
        ease: 'power3.out'
    });
    
    gsap.from('.hero-subtitle', {
        duration: 1,
        y: 30,
        opacity: 0,
        delay: 0.3,
        ease: 'power3.out'
    });
    
    gsap.from('.hero-actions', {
        duration: 1,
        y: 30,
        opacity: 0,
        delay: 0.6,
        ease: 'power3.out'
    });
    
    gsap.from('.stat-card', {
        duration: 1,
        y: 50,
        opacity: 0,
        stagger: 0.2,
        delay: 0.9,
        ease: 'power3.out'
    });
}

// Scroll-triggered animations
function initScrollAnimations() {
    gsap.registerPlugin(ScrollTrigger);
    
    // Animate NGO cards on scroll
    gsap.utils.toArray('.ngo-card').forEach((card, index) => {
        gsap.from(card, {
            scrollTrigger: {
                trigger: card,
                start: 'top 80%',
                toggleActions: 'play none none reverse'
            },
            duration: 0.8,
            y: 50,
            opacity: 0,
            delay: index * 0.1,
            ease: 'power3.out'
        });
    });
    
    // Animate section titles
    gsap.utils.toArray('.section-title').forEach(title => {
        gsap.from(title, {
            scrollTrigger: {
                trigger: title,
                start: 'top 85%'
            },
            duration: 1,
            y: 30,
            opacity: 0,
            ease: 'power3.out'
        });
    });
}

// Card hover animations
function initCardAnimations() {
    const cards = document.querySelectorAll('.ngo-card, .stat-card, .kpi-card');
    
    cards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            gsap.to(card, {
                duration: 0.3,
                y: -10,
                scale: 1.02,
                boxShadow: '0 20px 60px rgba(0, 0, 0, 0.15)',
                ease: 'power2.out'
            });
        });
        
        card.addEventListener('mouseleave', () => {
            gsap.to(card, {
                duration: 0.3,
                y: 0,
                scale: 1,
                boxShadow: '0 10px 30px rgba(0, 0, 0, 0.1)',
                ease: 'power2.out'
            });
        });
    });
}

// Progress bar animation
function animateProgressBar(element, targetWidth) {
    gsap.to(element, {
        duration: 1.5,
        width: targetWidth + '%',
        ease: 'power2.out'
    });
}

// Modal animations
function animateModalOpen(modalId) {
    const modal = document.getElementById(modalId);
    const content = modal.querySelector('.modal-content');
    
    gsap.set(modal, { display: 'flex' });
    
    gsap.from(modal, {
        duration: 0.3,
        opacity: 0
    });
    
    gsap.from(content, {
        duration: 0.4,
        y: -50,
        opacity: 0,
        ease: 'back.out(1.7)'
    });
}

function animateModalClose(modalId) {
    const modal = document.getElementById(modalId);
    const content = modal.querySelector('.modal-content');
    
    gsap.to(content, {
        duration: 0.3,
        y: -50,
        opacity: 0,
        ease: 'power2.in'
    });
    
    gsap.to(modal, {
        duration: 0.3,
        opacity: 0,
        onComplete: () => {
            modal.style.display = 'none';
        }
    });
}

// Toast notification animation
function animateToast(toastElement) {
    gsap.from(toastElement, {
        duration: 0.5,
        x: 100,
        opacity: 0,
        ease: 'back.out(1.7)'
    });
    
    gsap.to(toastElement, {
        duration: 0.5,
        x: 100,
        opacity: 0,
        delay: 2.5,
        ease: 'power2.in',
        onComplete: () => toastElement.remove()
    });
}

// Verified badge pulse animation
function pulseVerifiedBadge() {
    gsap.to('.verified-badge', {
        duration: 1,
        scale: 1.1,
        repeat: -1,
        yoyo: true,
        ease: 'power1.inOut'
    });
}

// Number counter animation
function animateCounter(element, target, duration = 2) {
    const obj = { value: 0 };
    
    gsap.to(obj, {
        duration: duration,
        value: target,
        ease: 'power1.out',
        onUpdate: () => {
            element.textContent = Math.round(obj.value).toLocaleString();
        }
    });
}

// Skeleton loader animation
function createSkeletonLoader(container) {
    gsap.to(container.querySelectorAll('.skeleton'), {
        duration: 1,
        opacity: 0.5,
        repeat: -1,
        yoyo: true,
        ease: 'power1.inOut'
    });
}

// Export functions for use in other files
window.TrustBridgeAnimations = {
    animateProgressBar,
    animateModalOpen,
    animateModalClose,
    animateToast,
    pulseVerifiedBadge,
    animateCounter,
    createSkeletonLoader
};
