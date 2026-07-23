

function sendCommand()
{

    let command =
        document.getElementById('command').value;


    fetch("<?= base_url('hardware/sendRaw') ?>",
    {

        method:"POST",

        headers:
        {
            "Content-Type":
            "application/x-www-form-urlencoded"
        },

        body:
        "deviceId=" + encodeURIComponent(deviceId) +
        "&command=" + encodeURIComponent(command)

    })

    .then(response => response.json())

    .then(data =>
    {

        document.getElementById('response').innerHTML =
            JSON.stringify(data,null,4);

    });

}

function scanModules()
{
    document.getElementById('response').innerHTML = "Scanning...";

    fetch("<?= base_url('hardware/scanModules') ?>", {
        method: "POST"
    })

    .then(response => response.json())

    .then(data => {

        let output = "";

        data.results.forEach(item => {

            output +=
                "Address: " + item.address +
                "\nTX: " + item.tx +
                "\nRX: " + item.rx +
                "\n---------------------\n";

        });


        document.getElementById('response').innerHTML = output;

    })

    .catch(error => {

        document.getElementById('response').innerHTML =
            "Error: " + error;

    });
}

function scanBaud()
{

    document.getElementById('response').innerHTML =
        "Scanning baud rates...";


    fetch("<?= base_url('hardware/readConfig') ?>",{

        method:"POST",

        headers:{
            "Content-Type":"application/x-www-form-urlencoded"
        },

        body:
        "deviceId=" + encodeURIComponent(deviceId)

    })

    .then(response=>response.json())

    .then(data=>{


        let output="";


        data.results.forEach(item=>{


            output +=

            "Baud: " + item.baud +
            "\nTX: " + item.tx +
            "\nRX: " + item.rx +
            "\n";


            if(item.found)
            {
                output += ">>> RESPONSE FOUND <<<\n";
            }


            output +=
            "----------------------\n";


        });



        document.getElementById('response').innerHTML =
            output;


    });

}

function readConfig()
{

fetch("<?= base_url('hardware/readConfig') ?>",{

    method:"POST",

    headers:{
        "Content-Type":"application/x-www-form-urlencoded"
    },

    body:
    "deviceId=" + encodeURIComponent(deviceId)

})

.then(response=>response.json())

.then(data=>{

document.getElementById('response').innerHTML =
JSON.stringify(data,null,4);

});

}

function testRelay()
{

fetch("<?= base_url('hardware/readConfig') ?>",{

    method:"POST",

    headers:{
        "Content-Type":"application/x-www-form-urlencoded"
    },

    body:
    "deviceId=" + encodeURIComponent(deviceId)

})

.then(response=>response.json())

.then(data=>{

document.getElementById('response').innerHTML =
JSON.stringify(data,null,4);

});

}

function setRelay(relay, state)
{
    fetch("<?= base_url('hardware/setRelay') ?>", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body:

        "deviceId=" + encodeURIComponent(deviceId) +

        "&relay=" + encodeURIComponent(relay) +

        "&state=" + encodeURIComponent(state)
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('response').innerHTML = JSON.stringify(data, null, 4);
    })
    .catch(error => {
        document.getElementById('response').innerHTML = "Error: " + error;
    });
}

function readRelayStatus()
{
    fetch("<?= base_url('hardware/readRelayStatus') ?>",{

        method:"POST",

        headers:{
        "Content-Type":"application/x-www-form-urlencoded"
        },

        body:
        "deviceId=" + encodeURIComponent(deviceId)

    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('response').innerHTML = JSON.stringify(data, null, 4);
    });
}

checkConnection();

function checkConnection()
{
    fetch("<?= base_url('hardware/readConfig') ?>",{

        method:"POST",

        headers:{
            "Content-Type":"application/x-www-form-urlencoded"
        },

        body:
        "deviceId=" + encodeURIComponent(deviceId)

    })

    .then(r=>r.json())

    .then(data=>{

        if(data.success)
        {
            document.getElementById("connectionStatus").innerHTML =
                "🟢 Online (" + data.time + " ms)";
        }
        else
        {
            document.getElementById("connectionStatus").innerHTML =
                "🔴 Offline";
        }

    });
}

