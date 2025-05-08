document.querySelector(".theme-toggle").addEventListener("click", () => {
    const isDark = document.body.classList.toggle("dark-mode");
    localStorage.setItem("theme", isDark ? "dark" : "light");
    const icon = document.querySelector(".theme-toggle i");
    if (isDark) {
        icon.classList.remove("fa-moon");
        icon.classList.add("fa-sun");
        console.log("Switched to dark mode, icon: fa-sun");
    } else {
        icon.classList.remove("fa-sun");
        icon.classList.add("fa-moon");
        console.log("Switched to light mode, icon: fa-moon");
    }
});

window.addEventListener("DOMContentLoaded", () => {
    const theme = localStorage.getItem("theme");
    const icon = document.querySelector(".theme-toggle i");
    if (theme === "dark") {
        document.body.classList.add("dark-mode");
        icon.classList.remove("fa-moon");
        icon.classList.add("fa-sun");
        console.log("Loaded dark mode, icon: fa-sun");
    } else {
        document.body.classList.remove("dark-mode");
        icon.classList.remove("fa-sun");
        icon.classList.add("fa-moon");
        console.log("Loaded light mode, icon: fa-moon");
    }

    Promise.all([
        faceapi.nets.ssdMobilenetv1.loadFromUri('/models'),
        faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
        faceapi.nets.faceRecognitionNet.loadFromUri('/models')
    ]).then(() => {
        console.log("face-api.js models loaded");
    }).catch(err => {
        console.error("Error loading face-api.js models:", err);
    });
});

document.getElementById("loginForm").addEventListener("submit", function(event) {
    event.preventDefault();
    let isValid = true;

    document.querySelectorAll(".error-text").forEach(span => span.textContent = "");

    const email = document.querySelector("input[name='email']").value.trim();
    const password = document.querySelector("input[name='password']").value;
    const captcha = document.querySelector("input[name='captcha']").value.trim();
    const captchaAnswer = <?php echo json_encode($_SESSION['captcha_answer'] ?? null); ?>;

    if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        document.getElementById("emailError").textContent = "<?php echo $translations[$language]['error_email_invalid']; ?>";
        isValid = false;
    }
    if (password.length < 1) {
        document.getElementById("passwordError").textContent = "<?php echo $translations[$language]['error_password_empty']; ?>";
        isValid = false;
    }
    if (!captcha.match(/^\d+$/) || parseInt(captcha) !== captchaAnswer) {
        document.getElementById("captchaError").textContent = "<?php echo $translations[$language]['error_captcha_invalid']; ?>";
        isValid = false;
    }

    if (isValid) {
        this.submit();
    }
});

const tabs = document.querySelectorAll(".login-tab");
const contents = document.querySelectorAll(".login-content");
tabs.forEach(tab => {
    tab.addEventListener("click", () => {
        tabs.forEach(t => t.classList.remove("active"));
        contents.forEach(c => c.classList.remove("active"));
        tab.classList.add("active");
        document.getElementById(`${tab.dataset.tab}-login`).classList.add("active");
    });
});

const faceButton = document.getElementById("faceid-button");
const faceModal = document.getElementById("faceModal");
const faceScanner = document.getElementById("face-scanner");
const faceCanvas = document.getElementById("face-canvas");
const scanFaceButton = document.getElementById("scanFace");
const closeFaceModalButton = document.getElementById("closeFaceModal");
const faceError = document.getElementById("faceError");
let faceStream = null;

faceButton.addEventListener("click", () => {
    faceModal.style.display = "flex";
    navigator.mediaDevices.getUserMedia({ video: { facingMode: "user", width: 320, height: 240 } })
        .then((mediaStream) => {
            faceStream = mediaStream;
            faceScanner.srcObject = faceStream;
            faceScanner.play();
            console.log("Caméra Face ID démarrée");
        })
        .catch((err) => {
            faceError.textContent = "Erreur d'accès à la caméra : " + err.message;
            console.error("Erreur caméra Face ID :", err);
        });
});

scanFaceButton.addEventListener("click", async () => {
    const ctx = faceCanvas.getContext("2d");
    faceCanvas.width = faceScanner.videoWidth;
    faceCanvas.height = faceScanner.videoHeight;
    ctx.drawImage(faceScanner, 0, 0, faceCanvas.width, faceCanvas.height);
    const imageData = faceCanvas.toDataURL("image/jpeg");

    try {
        const response = await fetch('face_recognition.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ image: imageData })
        });
        const result = await response.json();
        if (result.success) {
            window.location.href = result.role === 'admin' ? 'dashboard.php' : 'info2.php';
        } else {
            faceError.textContent = result.message || "Échec de la reconnaissance faciale.";
        }
    } catch (err) {
        faceError.textContent = "Erreur serveur : " + err.message;
        console.error("Erreur Face ID :", err);
    }
});

closeFaceModalButton.addEventListener("click", () => {
    stopFaceScanner();
    faceModal.style.display = "none";
    faceError.textContent = "";
});

faceModal.addEventListener("click", (e) => {
    if (e.target === faceModal) {
        stopFaceScanner();
        faceModal.style.display = "none";
        faceError.textContent = "";
    }
});

function stopFaceScanner() {
    if (faceStream) {
        faceStream.getTracks().forEach(track => track.stop());
        faceStream = null;
    }
}