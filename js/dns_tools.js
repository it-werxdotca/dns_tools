(function (Drupal, once) {
  Drupal.behaviors.dnsTools = {
	attach: function (context, settings) {
	  once('dns-tools', 'form#dns-tools-form', context).forEach(function (form) {
		const submitButton = form.querySelector('input[type="submit"]');
		const outputContainer = form.querySelector('#dns-tools-output');

		if (submitButton && outputContainer) {
		  submitButton.addEventListener('click', function (e) {
			e.preventDefault();

			const formData = new FormData(form);
			const command = formData.get('command');
			const flags = formData.get('flags');
			const target = formData.get('target');

			fetch('/dns-tools/run', {
			  method: 'POST',
			  headers: {
				'Content-Type': 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
			  },
			  body: JSON.stringify({ command, flags, target }),
			})
			  .then(response => response.json())
			  .then(data => {
				outputContainer.innerHTML = data.output;
			  })
			  .catch(error => {
				outputContainer.innerHTML = '<p>An error occurred while running the command.</p>';
				console.error(error);
			  });
		  });
		}
	  });
	},
  };
})(Drupal, once);