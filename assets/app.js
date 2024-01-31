/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';

// start the Stimulus application
import './bootstrap';

window.menu = function(){
    let navigation = document.querySelector(".navigation");
    let closeButton = document.querySelector(".navigation .close");
    let body = document.querySelector("body");

    if (navigation.style.display === "none" || navigation.style.display === "") {
        navigation.style.display = "flex";
        navigation.style.flexDirection = "column";
        navigation.style.width = "100vw";
        body.style.overflow = "hidden";
        closeButton.style.display = "block";
    } else {
        navigation.style.display = "none";
        navigation.style.flexDirection = "row";
        navigation.style.width = "calc(100% - 72px - 40px - 206.6px - 40px - 20px)";
        body.style.overflow = "auto";
        closeButton.style.display = "none";
    }
}
window.toggleMode = function(){
    const toggle_button_icon = document.querySelector(".mode i");

    let theme = localStorage.getItem("theme");

    if (theme === 'dark') {
        theme = 'light'
        document.documentElement.setAttribute('data-theme', 'light');
        toggle_button_icon.classList.replace("fa-sun", "fa-moon");
    } else {
        theme = 'dark'
        document.documentElement.setAttribute('data-theme', 'dark');
        toggle_button_icon.classList.replace("fa-moon", "fa-sun");
    }
    localStorage.setItem("theme", theme);
}
