import pandas as pd
import numpy as np
import re
import argparse
import csv
import datetime

pd.set_option('display.max_row',10)
pd.set_option('display.max_colwidth',225)

def find_meters(args):
    trendmeters = []
    df = pd.read_csv(args.input_file,
                     #nrows=400000,
                     converters={'Object Value': drop_units,'Date / Time': sortable_time})
    df.sort_values(by=['Object Name', 'Date / Time'], inplace=True)
    for trend in (df['Object Name'].unique()):
        z = (df[df['Object Name'] == trend]['Object Value'])
        summary = check_summarizable(np.array(z),args)
        trendmeters.append((trend,summary))
    return trendmeters


def drop_units(value):
    """Remove the units from a string, e.g. '309.2 kWh' -> 309.2"""
    #pattern = re.compile(r"\A(\d*\.?\d+) ?[a-zA-Z]*\Z")
    pattern = re.compile(r"\A([+\-]?(?:0|[1-9]\d*)(?:\.\d*)?(?:[eE][+\-]?\d+)?).*\Z")
    match = pattern.match(value)
    if match is None:
        # value was not of the expected format
        return np.nan
    else:
        return float(match.group(1))

def sortable_time(value):
    sortable = datetime.datetime.strptime( value, '%m/%d/%Y %H:%M:%S' )
    return sortable

def check_summarizable(series,args):
    boolseriesrising = ((series[1:] - series[:-1]) > 0)
    boolseriesconstant = ((series[1:] - series[:-1]) == 0)
    boolseriesfalling = ((series[1:] - series[:-1]) < 0)
    rising = boolseriesrising.mean()
    constant = boolseriesconstant.mean()
    falling = boolseriesfalling.mean()

    if constant > args.constant_threshold:
        return False
    elif rising / (rising + falling) > args.rising_threshold:
        return True
    else:
        return False


def write_meters(array,output_file):
    with open(output_file, mode='w', newline="", encoding='utf-8') as w:
        csvwriter = csv.writer(w)
        for entry in array:
            csvwriter.writerow(entry)



if __name__ == '__main__':
    parser = argparse.ArgumentParser(description='script to find meters in Metasys data files')
    parser.add_argument('-i', dest='input_file',  help='name of input file')
    parser.add_argument('-o', dest='output_file', help='name of output file')
    parser.add_argument('-c', dest='constant_threshold', type=float, help='threshold for detection of constant trend')
    parser.add_argument('-r', dest='rising_threshold', type=float, help='threshold for detection of rising meter')
    args = parser.parse_args()

    q = find_meters(args)
    write_meters(q,args.output_file)
