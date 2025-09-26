document.addEventListener('DOMContentLoaded', function () {
  let calendarEl = document.getElementById('calendar');

  let calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    locale: 'pt-br',
    selectable: true,
    editable: true,
    eventResizableFromStart: true,
    headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
    events: 'get_events.php',

    select: function (info) {
      fetch('get_services.php')
        .then(res => res.json())
        .then(services => {
          let serviceOptions = services.map(s => `${s.id}: ${s.name}`).join("\n");
          let serviceId = prompt(`Escolha um serviço pelo número:\n${serviceOptions}`);
          let selectedService = services.find(s => s.id == serviceId);
          if (!selectedService) { alert("Serviço inválido!"); return; }

          let start = info.start, end = info.end;
          let date = start.toISOString().split('T')[0];
          let time = start.toTimeString().split(' ')[0].substring(0, 5);
          let end_time = end.toTimeString().split(' ')[0].substring(0, 5);

          fetch('add_from_calendar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `service=${encodeURIComponent(selectedService.name)}&date=${date}&time=${time}&end_time=${end_time}`
          }).then(res => res.json()).then(() => calendar.refetchEvents());
        });
    },

    eventDrop: function (info) {
      fetch('move_event.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: info.event.id, date: info.event.start.toISOString().split('T')[0], time: info.event.start.toTimeString().substring(0, 5) })
      }).then(res => res.json()).then(() => calendar.refetchEvents());
    },

    eventResize: function (info) {
      fetch('resize_event.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: info.event.id, end_time: info.event.end.toTimeString().substring(0, 5) })
      }).then(res => res.json()).then(() => calendar.refetchEvents());
    },

    eventClick: function (info) {
      let id = info.event.id;
      let currentColor = info.event.backgroundColor;
      let newStatus = (currentColor === '#28a745') ? 'agendado' : 'atendido';
      if (confirm(`Deseja marcar como ${newStatus}?`)) {
        fetch('update_status.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `id=${id}&status=${newStatus}` })
          .then(res => res.json()).then(() => calendar.refetchEvents());
      }
    }
  });

  calendar.render();
});
