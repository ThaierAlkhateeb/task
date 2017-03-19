var rootUrl = "http://localhost/task/api";

$('#btnLogin').click(function() {
    login($('#username').val(), $('#password').val());
    return false;
});
$('#btnLogout').click(function() {
    logout($('#token').val());
    return false;
});
$('#btnProject').click(function() {
    getBillableHours($('#proId').val());
    return false;
});
$('#btnPeak').click(function() {
    getPeakTime($('#day').val(), $('#projectId').val());
    return false;
});

function login(username, password) {
    var dataString = "username=" + username + "&password=" + password;
    $.ajax({
        type: 'POST',
        url: rootUrl + "/login",
        data: dataString,
        success: function(data) {
            $('#output').val(JSON.stringify(data, null, 4));
        },
        error: function(jqXHR, textStatus, errorThrown) {
            $('#output').val('error: ' + textStatus);
        }
    });
}
function logout(token) {
    var dataString = "token=" + token;
    $.ajax({
        type: 'POST',
        url: rootUrl + "/logout",
        data: dataString,
        success: function(data) {
            $('#output').val(JSON.stringify(data, null, 4));
        },
        error: function(jqXHR, textStatus, errorThrown) {
            alert(JSON.stringify(jqXHR, null, 4));
            $('#output').val('error: ' + textStatus);
        }
    });
}
function getBillableHours(id) {
    $.ajax({
        type: 'GET',
        contentType: 'application/json',
        url: rootUrl + "/project",
        data: "id=" + id,
        success: function(data) {
            $('#output').val(JSON.stringify(data, null, 6));
        },
        error: function(jqXHR, textStatus, errorThrown) {
            $('#output').val('error: ' + textStatus);
        }
    });
}
function getPeakTime(day, projectId) {
    $.ajax({
        type: 'GET',
        contentType: 'application/json',
        url: rootUrl + "/statistic",
        data: "day=" + day + "&project=" + projectId,
        success: function(data) {
            $('#output').val(JSON.stringify(data, null, 6));
        },
        error: function(jqXHR, textStatus, errorThrown) {
            $('#output').val('error: ' + textStatus);
        }
    });
}

