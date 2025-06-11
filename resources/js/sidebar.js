/**
 * Sidebar Toggle Functionality
 * Handles collapsing sidebar to icon-only view instead of hiding completely
 */

// Immediately apply sidebar state to prevent flash of content
(function() {
  const isMobile = window.innerWidth < 768;
  const isCollapsed = localStorage.getItem('sidebarState') === 'collapsed';
  
  // Disable transitions during page load
  document.documentElement.classList.add('no-transition');
  
  // Add classes to HTML before DOM is fully loaded to prevent flashing
  if (isMobile) {
    document.documentElement.classList.add('sidebar-mobile');
  } else if (isCollapsed) {
    document.documentElement.classList.add('sidebar-collapsed');
  }
  
  // Remove no-transition class as soon as possible
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', removeNoTransition);
  } else {
    removeNoTransition();
  }
  
  function removeNoTransition() {
    requestAnimationFrame(() => {
      document.documentElement.classList.remove('no-transition');
    });
  }
})();

// Store current URL to detect page changes
let currentUrl = window.location.href;

document.addEventListener('DOMContentLoaded', function() {
  // Get DOM elements
  const sidebarCollapse = document.getElementById('sidebarCollapse');
  const sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');
  const sidebar = document.getElementById('sidebar');
  const content = document.getElementById('content');
  
  console.log("Sidebar Toggle Script Loaded");
  
  // Set initial state
  let isMobile = window.innerWidth < 768;
  let isCollapsed = localStorage.getItem('sidebarState') === 'collapsed';
  
  // Apply initial state immediately
  if (isMobile) {
    sidebar.classList.add('active');
    content.classList.add('active');
  } else if (isCollapsed) {
    sidebar.classList.add('collapsed');
    content.classList.add('collapsed');
  }
  
  // Remove pre-render classes
  document.documentElement.classList.remove('sidebar-mobile', 'sidebar-collapsed');
  
  // Toggle function for desktop
  function toggleDesktopSidebar() {
    console.log("Desktop toggle clicked");
    if (sidebar.classList.contains('collapsed')) {
      // Expand
      sidebar.classList.remove('collapsed');
      content.classList.remove('collapsed');
      localStorage.setItem('sidebarState', 'expanded');
    } else {
      // Collapse to icon only
      sidebar.classList.add('collapsed');
      content.classList.add('collapsed');
      localStorage.setItem('sidebarState', 'collapsed');
    }
  }
  
  // Toggle function for mobile
  function toggleMobileSidebar() {
    console.log("Mobile toggle clicked");
    if (sidebar.classList.contains('active')) {
      // Show
      sidebar.classList.remove('active');
      content.classList.remove('active');
    } else {
      // Hide
      sidebar.classList.add('active');
      content.classList.add('active');
    }
  }
  
  // Add event listeners
  if (sidebarCollapse) {
    sidebarCollapse.addEventListener('click', function(e) {
      e.preventDefault();
      console.log("Hamburger button clicked");
      
      if (window.innerWidth < 768) {
        toggleMobileSidebar();
      } else {
        toggleDesktopSidebar();
      }
    });
  }
  
  if (sidebarCollapseBtn) {
    sidebarCollapseBtn.addEventListener('click', function(e) {
      e.preventDefault();
      toggleMobileSidebar();
    });
  }
  
  // Handle window resize
  window.addEventListener('resize', function() {
    const wasDesktop = !isMobile;
    isMobile = window.innerWidth < 768;
    
    // If we switched between mobile and desktop
    if (wasDesktop !== !isMobile) {
      // Reset classes
      sidebar.classList.remove('active', 'collapsed');
      content.classList.remove('active', 'collapsed');
      
      // Apply appropriate classes
      if (isMobile) {
        sidebar.classList.add('active');
        content.classList.add('active');
      } else if (localStorage.getItem('sidebarState') === 'collapsed') {
        sidebar.classList.add('collapsed');
        content.classList.add('collapsed');
      }
    }
  });
  
  // Handle page navigation via Turbo/PJAX if available
  if (typeof window.Turbo !== 'undefined') {
    document.addEventListener('turbo:before-render', function() {
      document.documentElement.classList.add('no-transition');
    });
    
    document.addEventListener('turbo:render', function() {
      requestAnimationFrame(() => {
        document.documentElement.classList.remove('no-transition');
      });
    });
  }
}); 