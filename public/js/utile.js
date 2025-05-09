const escapeHTML = (str) => {
    if (typeof str !== 'string') return str;
    return str.replace(/[&<>"'\/]/g, char => ({
        '&': '&',
        '<': '<',
        '>': '>',
        '"': '"',
        "'": '',
        '/': '/'
    })[char]);
};