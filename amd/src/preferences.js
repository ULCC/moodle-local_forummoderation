import $ from "jquery";
export const init = () => {
    removePreferencesUser();
};

export const removePreferencesUser = () => {
    const nameprefence = "message_provider_local_forummoderation_forummoderation";
    const elements = document.querySelectorAll("tr");
    let previousElement = null;
    elements.forEach(element => {
        if (element.hasAttribute("data-preference-key")) {
            const preferenceKey = element.getAttribute("data-preference-key");
            if (preferenceKey === nameprefence) {
                $(element).hide();
                $(previousElement).hide();
            }
        }
        previousElement = element;
    });
};