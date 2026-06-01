(function () {
	'use strict';

	const cfg = window.ehnFullPageMessenger || {};
	const genesysScriptUrl = cfg.genesysBootstrapUrl || '';
	const genesysEnvironment = cfg.genesysEnvironment || 'prod-cac1';
	const genesysDeploymentId = cfg.genesysDeploymentId || '';
	const notificationIconUrl = cfg.notificationIconUrl || '';
	const cookieSecure = !!cfg.cookieSecure;

	const ROOT_ID = 'ehn-fpm-chat';

	let newMessageCount = 0;
	let originalTitle = document.title;
	let windowFocused = true;
	let agentJoinedShown = false;
	let lastReadoutNode = null;
	let lastReadoutSender = null;
	let lastReadoutTime = null;
	let typingTimeout;

	function getRoot() {
		return document.getElementById(ROOT_ID);
	}

	function getChatBody() {
		const root = getRoot();
		return root ? root.querySelector('.ehn-fpm-chat__body') : null;
	}

	function getSystemMessageEl() {
		return document.getElementById('ehn-fpm-system-message');
	}

	function getInputEl() {
		return document.getElementById('ehn-fpm-input');
	}

	function getSendBtn() {
		return document.getElementById('ehn-fpm-send');
	}

	function generateToken() {
		return crypto.randomUUID();
	}

	function setCookie(name, value, days) {
		const expires = new Date(Date.now() + days * 864e5).toUTCString();
		const secureFlag = cookieSecure ? '; Secure' : '';
		document.cookie =
			name +
			'=' +
			encodeURIComponent(value) +
			'; expires=' +
			expires +
			'; path=/; SameSite=Strict' +
			secureFlag;
	}

	function getCookie(name) {
		return document.cookie.split('; ').reduce(function (r, v) {
			const parts = v.split('=');
			return parts[0] === name ? decodeURIComponent(parts[1]) : r;
		}, '');
	}

	let chatToken = getCookie('chatToken') || generateToken();
	setCookie('chatToken', chatToken, 1);

	const MESSAGE_HISTORY_KEY = 'genesys_chat_history_' + chatToken;

	function saveMessageToHistory(entry) {
		const history = JSON.parse(localStorage.getItem(MESSAGE_HISTORY_KEY)) || [];

		const exists = history.some(function (item) {
			return (
				item.text === entry.text &&
				item.sender === entry.sender &&
				item.type === entry.type &&
				item.time === entry.time
			);
		});

		if (!exists) {
			history.push(entry);
			localStorage.setItem(MESSAGE_HISTORY_KEY, JSON.stringify(history));
		}
	}

	function formatTime(date) {
		if (!(date instanceof Date) || isNaN(date)) {
			return '--:--';
		}
		return (
			date.getHours().toString().padStart(2, '0') +
			':' +
			date.getMinutes().toString().padStart(2, '0')
		);
	}

	function clearQuickReplies() {
		const root = getRoot();
		if (!root) {
			return;
		}
		const quickReplyContainer = root.querySelector('.ehn-fpm-quick-replies');
		if (quickReplyContainer) {
			quickReplyContainer.remove();
		}
	}

	function displayQuickReplies(quickReplies) {
		const chatBodyEl = getChatBody();
		if (!chatBodyEl) {
			return;
		}

		clearQuickReplies();

		const quickReplyContainer = document.createElement('div');
		quickReplyContainer.classList.add('ehn-fpm-quick-replies');

		quickReplies.forEach(function (reply) {
			const button = document.createElement('button');
			button.type = 'button';
			button.classList.add('ehn-fpm-quick-replies__btn');
			button.innerText = reply.quickReply.text;
			button.onclick = function () {
				sendQuickReply(reply.quickReply.payload);
			};
			quickReplyContainer.appendChild(button);
		});

		chatBodyEl.appendChild(quickReplyContainer);
		chatBodyEl.scrollTop = chatBodyEl.scrollHeight;
	}

	function sendQuickReply(payload) {
		if (typeof window.Genesys !== 'function') {
			return;
		}
		window.Genesys('command', 'MessagingService.sendMessage', {
			message: payload,
		});

		displayMessage('You', payload, 'self');
		clearQuickReplies();
	}

	function displayTypingIndicator() {
		const systemMessage = getSystemMessageEl();
		if (systemMessage) {
			systemMessage.innerHTML = '<i>Agent is typing...</i>';
		}
	}

	function clearTypingIndicator() {
		const systemMessage = getSystemMessageEl();
		if (systemMessage) {
			systemMessage.innerHTML = '';
		}
	}

	function displayMessage(sender, message, type, time, skipSave, content) {
		message = typeof message === 'undefined' ? '' : message;
		type = typeof type === 'undefined' ? 'incoming' : type;
		time = typeof time === 'undefined' ? '' : time;
		skipSave = !!skipSave;
		content = typeof content === 'undefined' ? null : content;

		const chatBody = getChatBody();
		if (!chatBody) {
			return;
		}

		const rowClass = type === 'self' ? 'ehn-fpm-msg-row--self' : 'ehn-fpm-msg-row--incoming';
		const msgClass = type === 'self' ? 'ehn-fpm-msg--self' : 'ehn-fpm-msg--incoming';

		const messageContainer = document.createElement('div');
		messageContainer.classList.add('ehn-fpm-msg-row', rowClass);

		const messageElement = document.createElement('div');
		messageElement.classList.add('ehn-fpm-msg', msgClass);

		const safeMessage = typeof message === 'string' ? message : '';
		const parsedMessage = safeMessage.replace(
			/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/g,
			'<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>'
		);

		const messageText = document.createElement('div');
		messageText.classList.add('ehn-fpm-msg__text');
		messageText.innerHTML = parsedMessage;
		messageElement.appendChild(messageText);

		const displayTime = time || formatTime(new Date());
		if (
			lastReadoutNode &&
			sender === lastReadoutSender &&
			displayTime === lastReadoutTime
		) {
			lastReadoutNode.remove();
		}

		const readout = document.createElement('div');
		readout.classList.add('ehn-fpm-msg__readout');
		readout.innerHTML = sender + ' • ' + displayTime;
		messageElement.appendChild(readout);

		lastReadoutNode = readout;
		lastReadoutSender = sender;
		lastReadoutTime = displayTime;

		if (type === 'incoming') {
			clearTypingIndicator();
			const profilePic = document.createElement('div');
			profilePic.classList.add('ehn-fpm-msg__avatar');
			messageContainer.appendChild(profilePic);
		}

		messageContainer.appendChild(messageElement);

		if (type === 'self') {
			const profilePic = document.createElement('div');
			profilePic.classList.add('ehn-fpm-msg__avatar');
			messageContainer.appendChild(profilePic);
		}

		chatBody.appendChild(messageContainer);

		if (type === 'incoming' && Array.isArray(content)) {
			const quickReplies = content.filter(function (c) {
				return c.contentType === 'QuickReply';
			});
			if (quickReplies.length > 0) {
				displayQuickReplies(quickReplies);
			}
		}

		chatBody.scrollTop = chatBody.scrollHeight;

		if (!windowFocused && type === 'incoming') {
			newMessageCount++;
			document.title = '(' + newMessageCount + ') ' + originalTitle;

			if ('Notification' in window && Notification.permission === 'granted') {
				const notifOptions = { body: message };
				if (notificationIconUrl) {
					notifOptions.icon = notificationIconUrl;
				}
				const notification = new Notification(sender, notifOptions);
				notification.onclick = function () {
					window.focus();
				};
			}
		}

		if (!skipSave) {
			saveMessageToHistory({
				sender: sender,
				text: message,
				type: type,
				time: time || new Date().toISOString(),
				content: content,
			});
		}
	}

	function loadHistoryToChat() {
		const history = JSON.parse(localStorage.getItem(MESSAGE_HISTORY_KEY)) || [];

		history.forEach(function (rec, index) {
			const date = new Date(rec.time);
			const isLast = index === history.length - 1;

			displayMessage(
				rec.sender,
				rec.text,
				rec.type,
				formatTime(date),
				true,
				isLast ? rec.content : null
			);
		});

		const chatBody = getChatBody();
		if (chatBody) {
			chatBody.scrollTop = chatBody.scrollHeight;
		}
	}

	function clearHistory() {
		localStorage.removeItem(MESSAGE_HISTORY_KEY);
		document.title = originalTitle;
		newMessageCount = 0;
	}

	function bindGenesysSubscriptions() {
		if (typeof window.Genesys !== 'function') {
			return;
		}

		window.Genesys('subscribe', 'MessagingService.ready', function () {
			const joinSentKey = 'user_join_sent_' + chatToken;
			const alreadySent = localStorage.getItem(joinSentKey);

			if (!alreadySent) {
				window.Genesys('command', 'MessagingService.sendMessage', {
					message: 'A user has joined the chat.',
				});
				localStorage.setItem(joinSentKey, 'true');
			}
		});

		window.Genesys('subscribe', 'MessagingService.ended', clearHistory);

		window.Genesys('subscribe', 'MessagingService.messagesReceived', function (data) {
			const messages = data && data.data && data.data.messages;
			if (!Array.isArray(messages)) {
				return;
			}

			messages.forEach(function (msg) {
				if (msg.direction === 'Outbound') {
					const sender =
						msg.originatingEntity === 'Bot' ? 'Chat assistant' : 'Agent';
					displayMessage(sender, msg.text, 'incoming', '', false, msg.content);

					clearQuickReplies();

					if (Array.isArray(msg.content)) {
						const quickReplies = msg.content.filter(function (c) {
							return c.contentType === 'QuickReply';
						});
						if (quickReplies.length > 0) {
							displayQuickReplies(quickReplies);
						}
					}
				}
			});
		});

		window.Genesys('subscribe', 'MessagingService.typingReceived', function (evt) {
			const data = evt && evt.data;
			displayTypingIndicator();

			if (!agentJoinedShown) {
				agentJoinedShown = true;

				const chatBodyEl = getChatBody();
				if (chatBodyEl) {
					const systemNotice = document.createElement('div');
					systemNotice.classList.add('ehn-fpm-chat__inline-notice');
					systemNotice.innerText = 'An agent has joined the chat';
					chatBodyEl.appendChild(systemNotice);
					chatBodyEl.scrollTop = chatBodyEl.scrollHeight;
				}
			}

			if (typingTimeout) {
				clearTimeout(typingTimeout);
			}
			typingTimeout = setTimeout(function () {
				clearTypingIndicator();
			}, (data && data.typing && data.typing.durationMs) || 5000);
		});

		window.Genesys('subscribe', 'MessagingService.typingTimeout', function () {
			clearTypingIndicator();
		});
	}

	function bootstrapGenesys() {
		if (!genesysScriptUrl || !genesysDeploymentId) {
			return;
		}
		(function (g, e, n, es, ys) {
			g['_genesysJs'] = e;
			g[e] =
				g[e] ||
				function () {
					(g[e].q = g[e].q || []).push(arguments);
				};
			g[e].t = 1 * new Date();
			g[e].c = es;
			ys = document.createElement('script');
			ys.async = 1;
			ys.src = n;
			ys.charset = 'utf-8';
			ys.onload = bindGenesysSubscriptions;
			document.head.appendChild(ys);
		})(window, 'Genesys', genesysScriptUrl, {
			environment: genesysEnvironment,
			deploymentId: genesysDeploymentId,
			identity: {
				token: chatToken,
			},
		});

		if (typeof window.Genesys === 'function') {
			bindGenesysSubscriptions();
		}
	}

	window.addEventListener('focus', function () {
		windowFocused = true;
		newMessageCount = 0;
		document.title = originalTitle;
	});

	window.addEventListener('blur', function () {
		windowFocused = false;
	});

	document.addEventListener('DOMContentLoaded', function () {
		const chatBody = getChatBody();
		const sendBtn = getSendBtn();
		const chatInput = getInputEl();

		if (!getRoot() || !chatBody || !sendBtn || !chatInput) {
			return;
		}

		loadHistoryToChat();

		if ('Notification' in window && Notification.permission === 'default') {
			Notification.requestPermission();
		}

		if (genesysScriptUrl && genesysDeploymentId) {
			bootstrapGenesys();
		}

		sendBtn.addEventListener('click', function () {
			const userMessage = chatInput.value.trim();
			if (!userMessage || typeof window.Genesys !== 'function') {
				return;
			}

			window.Genesys('command', 'MessagingService.sendMessage', {
				message: userMessage,
			});

			displayMessage('You', userMessage, 'self');
			chatBody.scrollTop = chatBody.scrollHeight;

			chatInput.value = '';
		});

		chatInput.addEventListener('keypress', function (e) {
			if (e.key === 'Enter' && e.target.value.trim() && typeof window.Genesys === 'function') {
				const userMessage = e.target.value.trim();
				window.Genesys('command', 'MessagingService.sendMessage', {
					message: userMessage,
				});
				displayMessage('You', userMessage, 'self');
				chatBody.scrollTop = chatBody.scrollHeight;

				e.target.value = '';
			}
		});

		chatInput.addEventListener('input', function () {
			chatInput.style.height = 'auto';
			chatInput.style.height = chatInput.scrollHeight + 'px';
		});
	});
})();
