import { attempt } from "lodash";

var helper = {
    getSiteUrl: function (path = "") {
        return window.location.origin + path;
    },

    validateEmail: function (email) {
        var re =
            /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    },

    validateDomain: function (domain) {
        var re =
            /^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9](?:\.[a-zA-Z]{2,})+$/;
        return re.test(String(domain).toLowerCase());
    },

    alertError(msg) {
        $("#errorModalContent").html(msg);
        $("#errorModal").modal("show");
        $(".modal").css({ "overflow-y": "scroll" })

    },
    alertMsg(title = '', msg = '') {
        let modal = $("#messageModal");
        modal.find("#messageModelTitle").html(title);
        modal.find("#messageModelContent").html(msg);
        modal.modal("show");
        $(".modal").css({ "overflow-y": "scroll" });

    },

    confirm(title, msg, yesCallback, cancelCallback) {
        $(".modal").css({ "overflow-y": "scroll" });

        if (title) {
            $("#confirmModalHeader").html(title);
        } else {
            $("#confirmModalHeader").html('Are you sure?');
        }

        if (msg) {
            $("#confirmModalContent").html(msg);
        } else {
            $("#confirmModalContent").html('This action is irreversible!');
        }

        $("#confirmModal").modal("show");

        $("#confirmBtnOk").off("click");
        $("#confirmBtnOk").on("click", (e) => {
            e.stopImmediatePropagation();
            $("#confirmModal").modal("hide");

            yesCallback();
        });

        $("#confirmBtnCancel").off("click");
        $("#confirmBtnCancel").on("click", (e) => {
            e.stopImmediatePropagation();
            $("#confirmModal").modal("hide");

            cancelCallback();
        });
    },

    confirm2(title, msg, yesCallback, cancelCallback) {
        $(".modal").css({ "overflow-y": "scroll" });

        if (title) {
            $("#confirmModalHeader2").html(title);
        } else {
            $("#confirmModalHeader2").html('Are you sure?');
        }

        if (msg) {
            $("#confirmModalContent2").html(msg);
        } else {
            $("#confirmModalContent2").html('This action is irreversible!');
        }

        $("#confirmModal2").modal("show");

        $("#confirmBtnOk2").off("click");
        $("#confirmBtnOk2").on("click", (e) => {
            e.stopImmediatePropagation();
            $("#confirmModal2").modal("hide");

            yesCallback();
        });

        $("#confirmBtnCancel2").off("click");
        $("#confirmBtnCancel2").on("click", (e) => {
            e.stopImmediatePropagation();
            $("#confirmModal2").modal("hide");

            cancelCallback();
        });
    },

    roundToHalf(num) {
        return Math.round(num * 2) / 2;
    },

    reloadPage() {
        window.location.reload();
    },

    serverErrorMessage() {
        return "An error occured, please try again or contact support if the problem persists.";
    },

    inchesToFeet(num) {
        return num / 12;
    },

    feetToInches(num) {
        return num * 12;
    },

    getDecimal(num) {
        return num % 1;
    },

    isMobilePhone() {
        return window.matchMedia("only screen and (max-width: 800px)").matches;
    },

    isTablet() {
        return window.matchMedia("only screen and (min-device-width: 768px) and (max-device-width: 1366px) and (-webkit-min-device-pixel-ratio: 1)").matches;
    },

    redirectTo(url) {
        window.location.href = url;
    },

    urlContains(text) {
        return window.location.href.indexOf(text) != -1;
    },
    stringContains(haystack, needle) {
        return haystack.indexOf(needle) != -1;
    },

    async validateForm(form) {
        if (form instanceof HTMLElement) {
            if (form.localName === "form") {
                let inputs = form.querySelectorAll("input");
                let errors = [];
                let loop = await inputs.forEach((input) => {
                    if (!("validation-except" in input.attributes)) {
                        //check all input types except FILE
                        if (!["file"].includes(input.type)) {
                            if (
                                "required" in input.attributes &&
                                !input.value.trim()
                            ) {
                                errors.push({ node: input, type: "required" });
                            }
                            if ("validation-match" in input.attributes) {
                                let matchElement =
                                    input.attributes["validation-match"].value;
                                matchElement = form[matchElement];
                                let match = matchElement.value;
                                let target = input.value;
                                if (match.trim() != target.trim()) {
                                    errors.push({ node: input, type: "match" });
                                    errors.push({
                                        node: matchElement,
                                        type: "match",
                                    });
                                }
                            }
                        }
                    }
                });
                //delete old messages
                document
                    .querySelectorAll(".invalid-feedback")
                    .forEach((e) => e.remove());
                // display errors
                errors.forEach((e) => {
                    let errorMsg = e.node.attributes[e.type + "-error-msg"];
                    let classes = e.node.attributes["error-class"]?.value ?? "";
                    // set error styel on input fields
                    e.node.classList.add("is-invalid");
                    if (errorMsg) {
                        let html = `<div class="invalid-feedback ${classes}">${errorMsg.value}</div>`;
                        e.node.insertAdjacentHTML("afterend", html);
                    }
                });
                if (!errors.length) {
                    form.querySelectorAll("input").forEach((e) => {
                        if (!("validation-except" in e.attributes)) {
                            e.classList.remove("is-invalid");
                            e.classList.add("is-valid");
                        }
                    });
                    document
                        .querySelectorAll(".invalid-feedback")
                        .forEach((e) => e.remove());
                }
                return {
                    ok: errors.length ? false : true,
                    errors,
                };
            } else {
                console.error(
                    `validateForm() acept  HTML element type of <form>. * YOU GIVE US  <${form.localName}>`.toUpperCase()
                );
                return false;
            }
        } else {
            console.error(`validateForm() acept  HTML element.`.toUpperCase());
            return false;
        }
    },
    loadImageOn(input, into) {
        input = $(input);
        if (input.length) {
            input.on("change", (e) => {
                into = $(into);
                if (into.length) {
                    let image = e.target.files[0];
                    into.prop("src", URL.createObjectURL(image));
                }
            });
        }
    },

    //Returns Date instance
    parseDate: function (dateInputVal) {
        let dateOnly = dateInputVal.slice(0, 10);
        dateOnly = dateOnly.replace(/\//g, '-');
        const parts = dateOnly.split("-");
        return new Date(parts[0], parts[1] - 1, parts[2]);
    },
    parseUSDate: function (dateInputVal) {
        let dateOnly = dateInputVal.slice(0, 10);
        dateOnly = dateOnly.replace(/\//g, '-');
        const parts = dateOnly.split("-");
        return new Date(parts[2], parts[0] - 1, parts[1]);
    },

    formatDate: function (dateString) {
        const date = this.parseDate(dateString);

        const options = { month: "numeric", day: "numeric", year: "numeric" };
        const formatted = new Intl.DateTimeFormat("en", options).format(date);

        return formatted;
    },
    formatDateCustom: function (dateString, options) {
        const date = this.parseDate(dateString);
        const formatted = new Intl.DateTimeFormat("en", options).format(date);

        return formatted;
    },
    formatDateTime(dateString) {
        //Convert DateString into ISO 8601 date format
        const date = new Date(dateString.replace(/\s/, 'T'));
        var hours = date.getHours();
        var minutes = date.getMinutes();
        var ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12; // the hour '0' should be '12'
        minutes = minutes < 10 ? '0'+minutes : minutes;
        var strTime = hours + ':' + minutes + ' ' + ampm;
        return (date.getMonth()+1) + "/" + date.getDate() + "/" + date.getFullYear() + "  " + strTime;
    },
    formatDateUsa(dateString) {
        const date = this.parseDate(dateString);
        return new Intl.DateTimeFormat('en-US').format(date)
    },
    formatDateUsaDateString(dateString) {
        const date = this.parseUSDate(dateString);
        return new Intl.DateTimeFormat('en-US').format(date)
    },
    diffDays(dateString1, dateString2) {
        const d1 = new Date(dateString1);
        const d2 = new Date(dateString2);

        var diff = d2.getTime() - d1.getTime();

        return parseInt(diff / (1000 * 60 * 60 * 24));
    },
    isNextDay(dateString1, dateString2) {
        return this.diffDays(dateString1, dateString2) == 1;
    },
    isCutoffTime() {
        const date = new Date();
        return date.getHours() >= 20 && date.getMinutes() > 0;
    },
    getDateStringUsa(date) {
        let year = date.getFullYear();
        let month = `${date.getMonth() + 1}`;
        let day = `${date.getDate()}`;
        if (day.length == 1) day = "0" + day;
        if (month.length == 1) month = "0" + month;

        return month + "/" + day + "/" + year;
    },
    getDateString(date) {
        let year = date.getFullYear();
        let month = `${date.getMonth() + 1}`;
        let day = `${date.getDate()}`;
        if (day.length == 1) day = "0" + day;
        if (month.length == 1) month = "0" + month;

        return `${year}-${month}-${day}`;
    },
    getDateTimeString(date) {
        let year = date.getFullYear();
        let month = `${date.getMonth() + 1}`;
        let day = `${date.getDate()}`;
        if (day.length == 1) day = "0" + day;
        if (month.length == 1) month = "0" + month;

        var hours = date.getHours();
        var minutes = date.getMinutes();
        hours = hours % 12;
        hours = hours ? hours : 12; // the hour '0' should be '12'
        minutes = minutes < 10 ? '0'+minutes : minutes;
        var strTime = hours + ':' + minutes;

        //Return ISO 8601 date format
        return `${year}-${month}-${day}T${strTime}`;
    },
    isLastDayOfMonth(date) {
        return new Date(date.getTime() + 86400000).getDate() === 1;
    },
    tzOffset() {
        return new Date().getTimezoneOffset();
    },
    previousDay(date) {
        date.setDate(date.getDate() - 1);
        return date;
    },
    isWeekend(date) {
        return date.getDay() === 6 || date.getDay() === 0;
    },

    create_id: function () {
        return ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, (c) =>
            (
                c ^
                (crypto.getRandomValues(new Uint8Array(1))[0] & (15 >> (c / 4)))
            ).toString(16)
        );
    },
    parseZonePoints(points) {
        points = JSON.parse(points);
        return points.map((p) => {
            return {
                lat: parseFloat(p.lat),
                lng: parseFloat(p.lng),
            };
        });
    },
    orderOptions(target) {
        let options = $(target);
        let arr = options.map(function (_, o) { return { t: $(o).text(), v: o.value }; }).get();
        arr.sort(function (o1, o2) { return o1.t < o2.t ? 1 : o1.t > o2.t ? -1 : 0; });
        options.each(function (i, o) {
            o.value = arr[i].v;
            $(o).text(arr[i].t);
        });
    },
    cardNumberInput(target) {
        if (target) {
            let ccNumberInput = document.querySelector(target),
                ccNumberPattern = /^\d{0,16}$/g,
                ccNumberSeparator = " ",
                ccNumberInputOldValue,
                ccNumberInputOldCursor,

                mask = (value, limit, separator) => {
                    var output = [];
                    for (let i = 0; i < value.length; i++) {
                        if (i !== 0 && i % limit === 0) {
                            output.push(separator);
                        }

                        output.push(value[i]);
                    }

                    return output.join("");
                },
                unmask = (value) => value.replace(/[^\d]/g, ''),
                checkSeparator = (position, interval) => Math.floor(position / (interval + 1)),
                ccNumberInputKeyDownHandler = (e) => {
                    let el = e.target;
                    ccNumberInputOldValue = el.value;
                    ccNumberInputOldCursor = el.selectionEnd;
                },
                ccNumberInputInputHandler = (e) => {
                    let el = e.target,
                        newValue = unmask(el.value),
                        newCursorPosition;

                    if (newValue.match(ccNumberPattern)) {
                        newValue = mask(newValue, 4, ccNumberSeparator);

                        newCursorPosition =
                            ccNumberInputOldCursor - checkSeparator(ccNumberInputOldCursor, 4) +
                            checkSeparator(ccNumberInputOldCursor + (newValue.length - ccNumberInputOldValue.length), 4) +
                            (unmask(newValue).length - unmask(ccNumberInputOldValue).length);

                        el.value = (newValue !== "") ? newValue : "";
                    } else {
                        el.value = ccNumberInputOldValue;
                        newCursorPosition = ccNumberInputOldCursor;
                    }

                    el.setSelectionRange(newCursorPosition, newCursorPosition);

                    highlightCC(el.value);
                },
                highlightCC = (ccValue) => {
                    let ccCardType = '',
                        ccCardTypePatterns = {
                            amex: /^3/,
                            visa: /^4/,
                            mastercard: /^5/,
                            disc: /^6/,

                            genric: /(^1|^2|^7|^8|^9|^0)/,
                        };

                    for (const cardType in ccCardTypePatterns) {
                        if (ccCardTypePatterns[cardType].test(ccValue)) {
                            ccCardType = cardType;
                            break;
                        }
                    }
                }

            ccNumberInput.addEventListener('keydown', ccNumberInputKeyDownHandler);
            ccNumberInput.addEventListener('input', ccNumberInputInputHandler);
        }
    },

    removeFromArray(theArray, valueToRemove) {
        return theArray.splice(theArray.indexOf(valueToRemove), 1);
    },

    async getZoneSettings() {
        const settings = await $.get(helper.getSiteUrl(`/get/zone/settings`));

        return settings;
    },

    genId() {
        return (
            "id" +
            Math.floor(Math.random() * 99999999999999.66)
                .toString(36)
                .substring(1)
        );
    },

    showLoader() {
        $("#loader_image").modal('show');
    },

    hideLoader(modal) {
        setTimeout(()=>{
            $("#loader_image").modal('hide');

            if (modal) {
                $(".modal").css({ "overflow-y": "scroll" });
                $(`#${modal}`).modal();
            }
        }, 500)
    },



    openModal(modal) {
        $(`#${modal}`).modal();
    },

    closeModal(modal) {
        $(`#${modal}`).modal('hide');
        $(".modal").css({ "overflow-y": "scroll" });
    },

    inputNumber(inputClass) {
        const range = [...Array(100).keys()];
        $(`${inputClass}`).on('keyup', (e) => {
            const self = $(e.target);
            if (self.val()) {
                if ( ! range.includes(parseInt(self.val()))) {
                    self.val(1);
                } else {
                    self.val(parseInt(self.val()));
                }
            }
        });
    },

    async getCurrentUserRole() {
        return await $.get(`${helper.getSiteUrl()}/current/user/role`);
    },

    getInitialsFromName(nameString) {
        const fullName = nameString.split(' ');
        const initials = fullName.shift().charAt(0) + fullName.pop().charAt(0);
        return initials.toUpperCase();
    },

    initialUppercase(text) {
        if (typeof text !== 'string') return '';
        return text.charAt(0).toUpperCase() + text.slice(1);
    },

    initialUppercaseWord(mySentence) {
        if (typeof mySentence !== 'string') return '';

        const words = mySentence.split(" ");
        for (let i = 0; i < words.length; i++) {
            if (words[i] != '' && typeof words[i] != 'undefined') {
                words[i] = words[i][0].toUpperCase() + words[i].substr(1).toLowerCase();
            }
        }

        return words.join(" ");
    },

    loadImagePreview(event, into) {
        if(event.target.files.length > 0){
            const src = URL.createObjectURL(event.target.files[0]);
            const preview = document.getElementById(into);
            preview.src = src;
        }
    },

    isSuperAdmin() {
        return $('#userRole').val() == 1;
    },

    log(msg) {
        console.log(msg);
        return false;
    },

    decodeHtml(html) {
        var txt = document.createElement("textarea");
        txt.innerHTML = html;
        return txt.value;
    },

    removeEmptyObjectFromArray(theArray = []) {
        const filteredArray = theArray.filter(obj => {
            if (Object.keys(obj).length !== 0) {
              return true;
            }

            return false;
        });

        return filteredArray;
    },

    roundToDecimal(number, decimalPlaces) {
        const factor = Math.pow(10, decimalPlaces);
        return Math.round(number * factor) / factor;
    }
};

export default helper;
