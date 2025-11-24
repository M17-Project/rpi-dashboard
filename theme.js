
function toggleTheme(){
    const isLight = document.documentElement.classList.toggle('light');
    try { localStorage.setItem('t', isLight ? 'l' : 'd'); } catch(e){}
}
