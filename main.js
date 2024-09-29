
let main;

/**
 * Main code block navigation
 * @param item
 * @returns {boolean}
 */
function navigateCodeBlock(item)
{
    item.classList.add('active');
    const index = getSiblings(item, (elem) => {
        elem.classList.remove('active');
    });
    codeBlock(index);
    if(getMain().classList.contains("active")) {
        getMain().classList.remove("active");
    }
    return false;
}

/**
 * Open navigation on smaller screen sizes
 * @param button
 */
function openNavigation(button)
{
    if(!getMain().classList.contains("active")) {
        getMain().classList.add("active");
    } else {
        getMain().classList.remove("active");
    }
    return false;
}

/**
 * Will add show class to current code block
 * @param index
 */
function codeBlock(index)
{
    const codeBlocks = document.querySelectorAll(".code-block");
    for (let i = 0; i < codeBlocks.length; i++) {
        if(i === index) {
            codeBlocks[i].classList.add("show")
        } else {
            codeBlocks[i].classList.remove("show");
        }
    }
}

/**
 * Get siblings from active item
 * @param item
 * @param callback
 * @returns {number}
 */
function getSiblings(item, callback)
{
    let count = 0, index = 0;
    for (let sibling of item.parentNode.children) {
        if(sibling !== item) {
            callback(sibling, count);
        } else {
            index = count;
        }
        count++;
    }
    return index;
}


/**
 * Get main element
 * @returns {HTMLElement}
 */
function getMain()
{
    if(typeof main !== "object") {
        main = document.getElementById("main");
    }
    return main;
}